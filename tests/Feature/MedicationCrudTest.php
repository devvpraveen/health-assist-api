<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class MedicationCrudTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_crud_medications_and_schedules(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson("/api/v1/patients/{$patient->id}/medications", [
            'name' => 'Metformin',
            'dosage' => '500mg',
            'frequency_label' => 'twice daily',
            'route' => 'oral',
            'instructions' => 'Take with meals',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Metformin')
            ->assertJsonPath('data.disclaimer', config('medication.disclaimer'));

        $medicationId = $create->json('data.id');

        $this->getJson("/api/v1/patients/{$patient->id}/medications")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/patients/{$patient->id}/medications/{$medicationId}", [
            'dosage' => '850mg',
        ])->assertOk()
            ->assertJsonPath('data.dosage', '850mg');

        $schedule = $this->postJson("/api/v1/patients/{$patient->id}/medications/{$medicationId}/schedules", [
            'time_of_day' => '08:00',
            'timezone' => 'UTC',
            'days_of_week' => [1, 2, 3, 4, 5],
        ])->assertCreated()
            ->assertJsonPath('data.timezone', 'UTC');

        $scheduleId = $schedule->json('data.id');

        $this->getJson("/api/v1/patients/{$patient->id}/medications/{$medicationId}/schedules")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->patchJson("/api/v1/patients/{$patient->id}/medications/{$medicationId}/schedules/{$scheduleId}", [
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson("/api/v1/patients/{$patient->id}/medications/{$medicationId}/schedules/{$scheduleId}")
            ->assertNoContent();

        $this->deleteJson("/api/v1/patients/{$patient->id}/medications/{$medicationId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('medications', ['id' => $medicationId]);
    }

    public function test_branch_manager_can_view_medications(): void
    {
        [$user] = $this->createTenantUserWithOrg(roleSlug: 'branch_manager');
        $patient = Patient::factory()->forTenant($user->tenant)->create();
        $medication = Medication::factory()->forPatient($patient)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->getJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $medication->uuid);
    }
}
