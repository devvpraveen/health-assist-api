<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Roles\AssignRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformUserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeManage($request->user());

        $query = User::query()
            ->with(['roles', 'tenant'])
            ->latest('id');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->integer('tenant_id'));
        }

        return UserResource::collection($query->paginate(50));
    }

    public function store(Request $request, AssignRoleAction $assignRole, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'role_slug' => [
                'required',
                'string',
                Rule::exists('roles', 'slug')->where(fn ($q) => $q->whereNull('tenant_id')),
            ],
            'tenant_uuid' => ['nullable', 'uuid', 'exists:tenants,uuid'],
        ]);

        $role = Role::query()
            ->whereNull('tenant_id')
            ->where('slug', $validated['role_slug'])
            ->firstOrFail();

        $tenant = null;
        if (! empty($validated['tenant_uuid'])) {
            $tenant = Tenant::query()->where('uuid', $validated['tenant_uuid'])->firstOrFail();
        }

        if ($role->slug === 'super_admin' && $tenant !== null) {
            throw ValidationException::withMessages([
                'tenant_uuid' => ['Super admins must not be scoped to a tenant.'],
            ]);
        }

        if ($role->slug !== 'super_admin' && $tenant === null) {
            throw ValidationException::withMessages([
                'tenant_uuid' => ['Select a tenant for this role.'],
            ]);
        }

        $pivotTenantId = $tenant?->id
            ?? Tenant::query()->where('slug', 'healthassist-platform')->value('id')
            ?? Tenant::query()->orderBy('id')->value('id');

        if ($pivotTenantId === null) {
            throw ValidationException::withMessages([
                'tenant_uuid' => ['No tenant available to attach the role pivot.'],
            ]);
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'tenant_id' => $role->slug === 'super_admin' ? null : $tenant?->id,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $assignRole->handle($user, $role, (int) $pivotTenantId, null, $request->user());

        $auditLogger->log('users.created', $user, [
            'user_uuid' => $user->uuid,
            'role_slug' => $role->slug,
        ]);

        return (new UserResource($user->load(['roles', 'tenant'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, User $user, AssignRoleAction $assignRole, AuditLogger $auditLogger): UserResource
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'role_slug' => [
                'sometimes',
                'string',
                Rule::exists('roles', 'slug')->where(fn ($q) => $q->whereNull('tenant_id')),
            ],
        ]);

        if (array_key_exists('password', $validated)) {
            if ($validated['password']) {
                $user->password = $validated['password'];
            }
            unset($validated['password']);
        }

        $roleSlug = $validated['role_slug'] ?? null;
        unset($validated['role_slug']);

        $user->fill($validated);
        $user->save();

        if ($roleSlug) {
            $role = Role::query()->whereNull('tenant_id')->where('slug', $roleSlug)->firstOrFail();
            $pivotTenantId = $user->tenant_id
                ?? Tenant::query()->where('slug', 'healthassist-platform')->value('id')
                ?? Tenant::query()->orderBy('id')->value('id');

            if ($pivotTenantId === null) {
                throw ValidationException::withMessages([
                    'role_slug' => ['No tenant available to attach the role pivot.'],
                ]);
            }

            $user->roles()->detach();
            $assignRole->handle($user, $role, (int) $pivotTenantId, null, $request->user());
        }

        $auditLogger->log('users.updated', $user, [
            'user_uuid' => $user->uuid,
            'fields' => array_keys($validated),
        ]);

        return new UserResource($user->fresh()->load(['roles', 'tenant']));
    }

    public function destroy(Request $request, User $user, AuditLogger $auditLogger): Response
    {
        $this->authorizeManage($request->user());

        if ($request->user()?->id === $user->id) {
            abort(422, 'You cannot delete your own account.');
        }

        $auditLogger->log('users.deleted', $user, [
            'user_uuid' => $user->uuid,
            'email' => $user->email,
        ]);

        $user->tokens()->delete();
        $user->roles()->detach();
        $user->delete();

        return response()->noContent();
    }

    private function authorizeManage(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('users.manage')),
            403,
        );
    }
}
