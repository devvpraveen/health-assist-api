<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;

trait CreatesTenantUsers
{
    /**
     * @return array{0: User, 1: Organization, 2: Tenant}
     */
    protected function createTenantUserWithOrg(string $name = 'Tenant', string $roleSlug = 'organization_admin'): array
    {
        $tenant = Tenant::factory()->create(['name' => $name]);
        $organization = Organization::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->forTenant($tenant)->create();

        $role = Role::query()->where('slug', $roleSlug)->whereNull('tenant_id')->firstOrFail();
        $user->roles()->attach($role->id, [
            'tenant_id' => $tenant->id,
            'branch_id' => null,
            'branch_key' => 'none',
        ]);

        return [$user, $organization, $tenant];
    }

    protected function createSuperAdminUser(): User
    {
        $platformTenant = Tenant::factory()->create(['name' => 'Platform']);
        $user = User::factory()->superAdmin()->create();
        $role = Role::query()->where('slug', 'super_admin')->whereNull('tenant_id')->firstOrFail();
        $user->roles()->attach($role->id, [
            'tenant_id' => $platformTenant->id,
            'branch_id' => null,
            'branch_key' => 'none',
        ]);

        return $user;
    }
}
