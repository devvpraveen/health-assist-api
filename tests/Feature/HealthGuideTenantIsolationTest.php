<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class HealthGuideTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
    }

    public function test_tenant_cannot_view_other_tenant_conversation(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB] = $this->createTenantUserWithOrg('Tenant B');
        $patientA = Patient::factory()->forTenant($userA->tenant)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $uuid = $this->postJson('/api/v1/health-guide/conversations', [
            'patient_id' => $patientA->id,
        ])->assertCreated()->json('data.uuid');

        Sanctum::actingAs($userB);
        TenantContext::set($userB->tenant_id);

        $this->getJson("/api/v1/health-guide/conversations/{$uuid}")
            ->assertNotFound();

        $this->postJson("/api/v1/health-guide/conversations/{$uuid}/messages", [
            'content' => 'Hello from another tenant',
        ])->assertNotFound();
    }
}
