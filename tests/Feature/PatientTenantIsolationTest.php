<?php

namespace Tests\Feature;

use App\Models\HealthRecord;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PatientTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_tenant_cannot_access_another_tenants_patient(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        $patientB = Patient::factory()->forTenant($tenantB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $response = $this->getJson('/api/v1/patients/'.$patientB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_tenant_cannot_access_another_tenants_health_record(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        $patientB = Patient::factory()->forTenant($tenantB)->create();
        $recordB = HealthRecord::factory()->forPatient($patientB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $response = $this->getJson('/api/v1/patients/'.$patientB->id.'/health-records/'.$recordB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_tenant_cannot_access_another_tenants_document(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        $patientB = Patient::factory()->forTenant($tenantB)->create();
        $documentB = PatientDocument::factory()->forPatient($patientB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $response = $this->getJson('/api/v1/patients/'.$patientB->id.'/documents/'.$documentB->id);

        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }
}
