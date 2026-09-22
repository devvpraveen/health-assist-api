<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\RoleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Platform console roles only (super_admin + platform_*).
 * Tenant/clinic roles are managed via TenantStaffController.
 */
class PlatformRoleController extends Controller
{
    public function permissions(Request $request): AnonymousResourceCollection
    {
        $this->authorizeManage($request->user());

        return PermissionResource::collection(
            Permission::query()->orderBy('slug')->get()
        );
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeManage($request->user());

        $scope = $request->string('scope')->toString() ?: 'platform';

        $query = Role::query()
            ->whereNull('tenant_id')
            ->with('permissions')
            ->withCount(['permissions', 'users'])
            ->orderBy('name');

        if ($scope === 'platform' || $scope === '') {
            RoleCatalog::scopePlatformAdminRoles($query);
        } elseif ($scope === 'templates') {
            $query->whereIn('slug', RoleCatalog::TENANT_TEMPLATE_SLUGS);
        } elseif ($scope !== 'all') {
            RoleCatalog::scopePlatformAdminRoles($query);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return RoleResource::collection($query->get());
    }

    public function store(Request $request, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorizeManage($request->user());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:64',
                'alpha_dash:ascii',
                Rule::unique('roles', 'slug')->where(fn ($q) => $q->whereNull('tenant_id')),
            ],
            'permission_slugs' => ['sometimes', 'array'],
            'permission_slugs.*' => ['string', 'exists:permissions,slug'],
        ]);

        $slug = Str::slug((string) ($validated['slug'] ?? $validated['name']), '_');
        if ($slug === '' || $slug === RoleCatalog::PLATFORM_SUPER_ADMIN) {
            throw ValidationException::withMessages([
                'slug' => ['Choose a different slug. "super_admin" is reserved.'],
            ]);
        }

        if (! str_starts_with($slug, 'platform_')) {
            $slug = 'platform_'.$slug;
        }

        if (RoleCatalog::isTenantTemplateSlug($slug)) {
            throw ValidationException::withMessages([
                'slug' => ['Tenant template role names cannot be created as platform roles.'],
            ]);
        }

        if (Role::query()->whereNull('tenant_id')->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ['That role slug already exists.'],
            ]);
        }

        $role = Role::query()->create([
            'tenant_id' => null,
            'tenant_key' => 'system',
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        $this->syncPermissions($role, $validated['permission_slugs'] ?? []);

        $auditLogger->log('roles.created', $role, [
            'role_slug' => $role->slug,
            'permission_slugs' => $validated['permission_slugs'] ?? [],
            'scope' => 'platform',
        ]);

        return (new RoleResource($role->load('permissions')->loadCount(['permissions', 'users'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Role $role, AuditLogger $auditLogger): RoleResource
    {
        $this->authorizeManage($request->user());
        $this->assertPlatformAdminRole($role);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:64',
                'alpha_dash:ascii',
                Rule::unique('roles', 'slug')
                    ->where(fn ($q) => $q->whereNull('tenant_id'))
                    ->ignore($role->id),
            ],
            'permission_slugs' => ['sometimes', 'array'],
            'permission_slugs.*' => ['string', 'exists:permissions,slug'],
        ]);

        if ($role->slug === RoleCatalog::PLATFORM_SUPER_ADMIN) {
            if (array_key_exists('slug', $validated) && $validated['slug'] !== RoleCatalog::PLATFORM_SUPER_ADMIN) {
                throw ValidationException::withMessages([
                    'slug' => ['The super_admin role slug cannot be changed.'],
                ]);
            }
            unset($validated['permission_slugs']);
            $role->fill(collect($validated)->only(['name'])->all());
            $role->save();
            $role->permissions()->sync(Permission::query()->pluck('id'));
        } else {
            if (array_key_exists('slug', $validated)) {
                $slug = Str::slug($validated['slug'], '_');
                if (! str_starts_with($slug, 'platform_')) {
                    $slug = 'platform_'.$slug;
                }
                if ($slug === RoleCatalog::PLATFORM_SUPER_ADMIN || RoleCatalog::isTenantTemplateSlug($slug)) {
                    throw ValidationException::withMessages([
                        'slug' => ['That slug is reserved.'],
                    ]);
                }
                $validated['slug'] = $slug;
            }
            $role->fill(collect($validated)->only(['name', 'slug'])->all());
            $role->save();
            if (array_key_exists('permission_slugs', $validated)) {
                $this->syncPermissions($role, $validated['permission_slugs']);
            }
        }

        $auditLogger->log('roles.updated', $role, [
            'role_slug' => $role->slug,
            'fields' => array_keys($validated),
            'scope' => 'platform',
        ]);

        return new RoleResource($role->fresh()->load('permissions')->loadCount(['permissions', 'users']));
    }

    public function destroy(Request $request, Role $role, AuditLogger $auditLogger): Response
    {
        $this->authorizeManage($request->user());
        $this->assertPlatformAdminRole($role);

        if ($role->slug === RoleCatalog::PLATFORM_SUPER_ADMIN) {
            abort(422, 'The super_admin role cannot be deleted.');
        }

        if ($role->users()->exists()) {
            abort(422, 'Detach this role from all users before deleting it.');
        }

        $auditLogger->log('roles.deleted', $role, [
            'role_slug' => $role->slug,
            'scope' => 'platform',
        ]);

        $role->permissions()->detach();
        $role->delete();

        return response()->noContent();
    }

    /**
     * @param  list<string>  $slugs
     */
    private function syncPermissions(Role $role, array $slugs): void
    {
        $ids = Permission::query()->whereIn('slug', $slugs)->pluck('id');
        $role->permissions()->sync($ids);
    }

    private function assertPlatformAdminRole(Role $role): void
    {
        abort_unless(RoleCatalog::isPlatformAdminRole($role), 404);
    }

    private function authorizeManage(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('roles.manage')),
            403,
        );
    }
}
