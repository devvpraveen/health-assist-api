<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class HealthProfileTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_upsert_and_view_health_profile(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->getJson('/api/v1/patients/'.$patient->id.'/health-profile')
            ->assertNoContent();

        $this->putJson('/api/v1/patients/'.$patient->id.'/health-profile', [
            'medical_history' => 'Seasonal allergies',
            'conditions' => ['asthma'],
            'allergies' => ['pollen'],
            'lifestyle' => 'Runs weekly',
        ])
            ->assertCreated()
            ->assertJsonPath('data.medical_history', 'Seasonal allergies')
            ->assertJsonPath('data.conditions.0', 'asthma')
            ->assertJsonPath('data.allergies.0', 'pollen');

        $this->putJson('/api/v1/patients/'.$patient->id.'/health-profile', [
            'medical_history' => 'Updated history',
            'conditions' => ['asthma', 'hypertension'],
        ])
            ->assertOk()
            ->assertJsonPath('data.medical_history', 'Updated history')
            ->assertJsonCount(2, 'data.conditions');

        $this->getJson('/api/v1/patients/'.$patient->id.'/health-profile')
            ->assertOk()
            ->assertJsonPath('data.medical_history', 'Updated history');

        $this->assertDatabaseCount('patient_health_profiles', 1);
    }
}
