<?php

namespace Tests\Feature;

use App\Models\Package;
use Database\Seeders\ModulePlatformSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class TenantProvisionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModulePlatformSeeder::class);
    }

    public function test_super_admin_can_provision_tenant_and_organization(): void
    {
        $admin = $this->createSuperAdminUser();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/tenants/provision', [
            'name' => 'Northside Care Network',
            'slug' => 'northside-care',
            'package_key' => 'starter',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.tenant.slug', 'northside-care')
            ->assertJsonPath('data.organization.name', 'Northside Care Network');

        $this->assertDatabaseHas('tenants', ['slug' => 'northside-care']);
        $this->assertDatabaseHas('organizations', ['slug' => 'northside-care']);
        $this->assertTrue(Package::query()->where('key', 'starter')->exists());
    }

    public function test_super_admin_can_create_clinic_with_tenant_header(): void
    {
        $admin = $this->createSuperAdminUser();
        Sanctum::actingAs($admin);

        $provisioned = $this->postJson('/api/v1/tenants/provision', [
            'name' => 'Clinic Seed Org',
            'slug' => 'clinic-seed-org',
            'package_key' => 'starter',
        ])->assertCreated()->json('data');

        $tenantUuid = $provisioned['tenant']['uuid'];
        $organizationId = $provisioned['organization']['id'];

        $this->withHeader('X-Tenant-UUID', $tenantUuid)
            ->postJson('/api/v1/clinics', [
                'organization_id' => $organizationId,
                'name' => 'Seed Clinic',
                'city' => 'Bengaluru',
                'type' => 'clinic',
                'is_public' => true,
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Seed Clinic');
    }
}
