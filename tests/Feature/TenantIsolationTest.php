<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_cannot_see_organization_of_another_tenant(): void
    {
        [$userA, $orgA] = $this->createTenantUserWithOrg('Tenant A');
        [, $orgB] = $this->createTenantUserWithOrg('Tenant B');

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $this->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orgA->id);

        $response = $this->getJson('/api/v1/organizations/'.$orgB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_user_cannot_see_branch_of_another_tenant(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, $orgB] = $this->createTenantUserWithOrg('Tenant B');

        $branchB = Branch::factory()->forOrganization($orgB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $response = $this->getJson('/api/v1/branches/'.$branchB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    /**
     * @return array{0: User, 1: Organization, 2: Tenant}
     */
    private function createTenantUserWithOrg(string $name): array
    {
        $tenant = Tenant::factory()->create(['name' => $name]);
        $organization = Organization::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->forTenant($tenant)->create();

        $role = Role::query()->where('slug', 'organization_admin')->whereNull('tenant_id')->firstOrFail();
        $user->roles()->attach($role->id, [
            'tenant_id' => $tenant->id,
            'branch_id' => null,
            'branch_key' => 'none',
        ]);

        return [$user, $organization, $tenant];
    }
}
