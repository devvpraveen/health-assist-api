<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\Service;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ClinicTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_cannot_access_another_tenants_clinic(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, $orgB] = $this->createTenantUserWithOrg('Tenant B');

        $clinicB = Clinic::factory()->forOrganization($orgB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $response = $this->getJson('/api/v1/clinics/'.$clinicB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_tenant_cannot_access_another_tenants_provider(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, $orgB] = $this->createTenantUserWithOrg('Tenant B');

        $clinicB = Clinic::factory()->forOrganization($orgB)->create();
        $providerB = Provider::factory()->forClinic($clinicB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $response = $this->getJson('/api/v1/providers/'.$providerB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_tenant_cannot_access_another_tenants_service(): void
    {
        [$userA, $orgA] = $this->createTenantUserWithOrg('Tenant A');
        [, $orgB] = $this->createTenantUserWithOrg('Tenant B');

        $clinicA = Clinic::factory()->forOrganization($orgA)->create();
        $clinicB = Clinic::factory()->forOrganization($orgB)->create();
        $serviceB = Service::factory()->forClinic($clinicB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $response = $this->getJson('/api/v1/clinics/'.$clinicA->id.'/services/'.$serviceB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }
}
