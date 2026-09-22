<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Roles\AssignRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\RoleCatalog;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Tenant-scoped staff users + roles. Platform console uses /admin/* instead.
 */
class TenantStaffController extends Controller
{
    public function permissions(Request $request): AnonymousResourceCollection
    {
        $this->authorizeTenantManage($request->user(), 'roles.manage');

        return PermissionResource::collection(RoleCatalog::grantableTenantPermissions());
    }

    public function roles(Request $request): AnonymousResourceCollection
    {
        $this->authorizeTenantManage($request->user(), 'roles.manage');
        $tenantId = $this->requireTenantId($request);

        $roles = RoleCatalog::assignableForTenant($tenantId)->map(function (Role $role) use ($tenantId): Role {
            $role->setAttribute(
                'users_count',
                $role->users()->wherePivot('tenant_id', $tenantId)->count(),
            );

            return $role;
        });

        return RoleResource::collection($roles);
    }

    public function storeRole(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeTenantManage($request->user(), 'roles.manage');
        $tenantId = $this->requireTenantId($request);
        $grantable = RoleCatalog::grantableTenantPermissions()->pluck('slug')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                'alpha_dash:ascii',
                Rule::unique('roles', 'slug')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'permission_slugs' => ['sometimes', 'array'],
            'permission_slugs.*' => ['string', Rule::in($grantable)],
        ]);

        $slug = Str::slug((string) ($validated['slug'] ?? $validated['name']), '_');
        if ($slug === '' || RoleCatalog::isPlatformAdminSlug($slug) || RoleCatalog::isTenantTemplateSlug($slug)) {
            throw ValidationException::withMessages([
                'slug' => ['Choose a different slug. System and platform role names are reserved.'],
            ]);
        }

        if (Role::query()->where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ['That role slug already exists for this clinic.'],
            ]);
        }

        $role = Role::query()->create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        $this->syncTenantPermissions($role, $validated['permission_slugs'] ?? [], $grantable);

        $auditLogger->log('tenant.roles.created', $role, [
            'role_slug' => $role->slug,
            'tenant_id' => $tenantId,
        ]);

        return (new RoleResource($role->load('permissions')))
            ->response()
            ->setStatusCode(201);
    }

    public function updateRole(Request $request, Role $role, AuditLogger $auditLogger): RoleResource
    {
        $this->authorizeTenantManage($request->user(), 'roles.manage');
        $tenantId = $this->requireTenantId($request);
        $this->assertTenantCustomRole($role, $tenantId);

        $grantable = RoleCatalog::grantableTenantPermissions()->pluck('slug')->all();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:64',
                'alpha_dash:ascii',
                Rule::unique('roles', 'slug')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($role->id),
            ],
            'permission_slugs' => ['sometimes', 'array'],
            'permission_slugs.*' => ['string', Rule::in($grantable)],
        ]);

        if (array_key_exists('slug', $validated)) {
            $slug = Str::slug($validated['slug'], '_');
            if (RoleCatalog::isPlatformAdminSlug($slug) || RoleCatalog::isTenantTemplateSlug($slug)) {
                throw ValidationException::withMessages([
                    'slug' => ['That slug is reserved for system or platform roles.'],
                ]);
            }
            $validated['slug'] = $slug;
        }

        $role->fill(collect($validated)->only(['name', 'slug'])->all());
        $role->save();

        if (array_key_exists('permission_slugs', $validated)) {
            $this->syncTenantPermissions($role, $validated['permission_slugs'], $grantable);
        }

        $auditLogger->log('tenant.roles.updated', $role, [
            'role_slug' => $role->slug,
            'fields' => array_keys($validated),
        ]);

        return new RoleResource($role->fresh()->load('permissions'));
    }

    public function destroyRole(Request $request, Role $role, AuditLogger $auditLogger): Response
    {
        $this->authorizeTenantManage($request->user(), 'roles.manage');
        $tenantId = $this->requireTenantId($request);
        $this->assertTenantCustomRole($role, $tenantId);

        if ($role->users()->wherePivot('tenant_id', $tenantId)->exists()) {
            abort(422, 'Detach this role from all users before deleting it.');
        }

        $auditLogger->log('tenant.roles.deleted', $role, [
            'role_slug' => $role->slug,
            'tenant_id' => $tenantId,
        ]);

        $role->permissions()->detach();
        $role->delete();

        return response()->noContent();
    }

    public function users(Request $request): AnonymousResourceCollection
    {
        $this->authorizeTenantManage($request->user(), 'users.view');
        $tenantId = $this->requireTenantId($request);

        $query = User::query()
            ->where('tenant_id', $tenantId)
            ->with(['roles.permissions', 'tenant'])
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

        return UserResource::collection($query->paginate(50));
    }

    public function storeUser(Request $request, AssignRoleAction $assignRole, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeTenantManage($request->user(), 'users.manage');
        $tenantId = $this->requireTenantId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'role_slug' => ['required', 'string'],
        ]);

        $role = $this->resolveAssignableRole($tenantId, $validated['role_slug']);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'tenant_id' => $tenantId,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $assignRole->handle($user, $role, $tenantId, null, $request->user());

        $auditLogger->log('tenant.users.created', $user, [
            'user_uuid' => $user->uuid,
            'role_slug' => $role->slug,
            'tenant_id' => $tenantId,
        ]);

        return (new UserResource($user->load(['roles.permissions', 'tenant'])))
            ->response()
            ->setStatusCode(201);
    }

    public function updateUser(Request $request, User $user, AssignRoleAction $assignRole, AuditLogger $auditLogger): UserResource
    {
        $this->authorizeTenantManage($request->user(), 'users.manage');
        $tenantId = $this->requireTenantId($request);
        $this->assertTenantUser($user, $tenantId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'role_slug' => ['sometimes', 'string'],
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
            $role = $this->resolveAssignableRole($tenantId, $roleSlug);
            $user->roles()->detach();
            $assignRole->handle($user, $role, $tenantId, null, $request->user());
        }

        $auditLogger->log('tenant.users.updated', $user, [
            'user_uuid' => $user->uuid,
            'fields' => array_keys($validated),
        ]);

        return new UserResource($user->fresh()->load(['roles.permissions', 'tenant']));
    }

    public function destroyUser(Request $request, User $user, AuditLogger $auditLogger): Response
    {
        $this->authorizeTenantManage($request->user(), 'users.manage');
        $tenantId = $this->requireTenantId($request);
        $this->assertTenantUser($user, $tenantId);

        if ($request->user()?->id === $user->id) {
            abort(422, 'You cannot delete your own account.');
        }

        $auditLogger->log('tenant.users.deleted', $user, [
            'user_uuid' => $user->uuid,
            'email' => $user->email,
            'tenant_id' => $tenantId,
        ]);

        $user->tokens()->delete();
        $user->roles()->detach();
        $user->delete();

        return response()->noContent();
    }

    /**
     * @param  list<string>  $slugs
     * @param  list<string>  $grantable
     */
    private function syncTenantPermissions(Role $role, array $slugs, array $grantable): void
    {
        $allowed = array_values(array_intersect($slugs, $grantable));
        $ids = Permission::query()->whereIn('slug', $allowed)->pluck('id');
        $role->permissions()->sync($ids);
    }

    private function resolveAssignableRole(int $tenantId, string $slug): Role
    {
        $role = RoleCatalog::assignableForTenant($tenantId)
            ->first(fn (Role $candidate): bool => $candidate->slug === $slug);

        if ($role === null) {
            throw ValidationException::withMessages([
                'role_slug' => ['That role is not available for this clinic.'],
            ]);
        }

        if (RoleCatalog::isPlatformAdminRole($role)) {
            throw ValidationException::withMessages([
                'role_slug' => ['Platform admin roles cannot be assigned to clinic users.'],
            ]);
        }

        return $role;
    }

    private function assertTenantCustomRole(Role $role, int $tenantId): void
    {
        abort_unless((int) $role->tenant_id === $tenantId, 404);
    }

    private function assertTenantUser(User $user, int $tenantId): void
    {
        abort_unless((int) $user->tenant_id === $tenantId, 404);
    }

    private function requireTenantId(Request $request): int
    {
        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        if ($tenantId === null) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $tenantId;
    }

    private function authorizeTenantManage(?User $user, string $permission): void
    {
        abort_unless(
            $user !== null && (
                $user->isSuperAdmin()
                || $user->hasPermission($permission)
                || $user->hasRole('clinic_admin')
                || $user->hasRole('organization_admin')
            ),
            403,
        );
    }
}
