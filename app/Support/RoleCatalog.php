<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Separates platform-console roles from tenant/clinic roles.
 *
 * - Platform admin roles: super_admin + platform_* (managed in apps/admin)
 * - System templates: patient / provider / clinic_admin / … (seeded; assignable by tenants)
 * - Tenant custom roles: roles.tenant_id = current tenant (managed by clinic admins)
 */
final class RoleCatalog
{
    public const PLATFORM_SUPER_ADMIN = 'super_admin';

    /** @var list<string> */
    public const TENANT_TEMPLATE_SLUGS = [
        'organization_admin',
        'branch_manager',
        'clinic_admin',
        'provider',
        'patient',
    ];

    public static function isPlatformAdminSlug(string $slug): bool
    {
        return $slug === self::PLATFORM_SUPER_ADMIN
            || str_starts_with($slug, 'platform_');
    }

    public static function isPlatformAdminRole(Role $role): bool
    {
        return $role->tenant_id === null && self::isPlatformAdminSlug($role->slug);
    }

    public static function isTenantTemplateSlug(string $slug): bool
    {
        return in_array($slug, self::TENANT_TEMPLATE_SLUGS, true);
    }

    public static function isTenantTemplateRole(Role $role): bool
    {
        return $role->tenant_id === null && self::isTenantTemplateSlug($role->slug);
    }

    /**
     * @param  Builder<Role>  $query
     * @return Builder<Role>
     */
    public static function scopePlatformAdminRoles(Builder $query): Builder
    {
        return $query->whereNull('tenant_id')
            ->where(function (Builder $builder): void {
                $builder->where('slug', self::PLATFORM_SUPER_ADMIN)
                    ->orWhere('slug', 'like', 'platform_%');
            });
    }

    /**
     * Roles a tenant may assign to its staff (system templates + own custom roles).
     *
     * @return Collection<int, Role>
     */
    public static function assignableForTenant(int $tenantId): Collection
    {
        $templates = Role::query()
            ->whereNull('tenant_id')
            ->whereIn('slug', self::TENANT_TEMPLATE_SLUGS)
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $custom = Role::query()
            ->where('tenant_id', $tenantId)
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return $templates->concat($custom)->values();
    }

    /**
     * Permissions a tenant admin may grant on custom roles (org-admin capability set).
     *
     * @return Collection<int, Permission>
     */
    public static function grantableTenantPermissions(): Collection
    {
        $orgAdmin = Role::query()
            ->whereNull('tenant_id')
            ->where('slug', 'organization_admin')
            ->with('permissions')
            ->first();

        if ($orgAdmin === null) {
            return Permission::query()
                ->whereNotIn('slug', ['tenants.view', 'tenants.manage', 'languages.manage'])
                ->orderBy('slug')
                ->get();
        }

        return $orgAdmin->permissions->sortBy('slug')->values();
    }

    /**
     * @return list<array{slug: string, label: string, source: string}>
     */
    public static function tenantRoleTemplatesMeta(): array
    {
        return [
            ['slug' => 'clinic_admin', 'label' => 'Clinic admin', 'source' => 'system'],
            ['slug' => 'organization_admin', 'label' => 'Organization admin', 'source' => 'system'],
            ['slug' => 'branch_manager', 'label' => 'Branch manager', 'source' => 'system'],
            ['slug' => 'provider', 'label' => 'Doctor / provider', 'source' => 'system'],
        ];
    }
}
