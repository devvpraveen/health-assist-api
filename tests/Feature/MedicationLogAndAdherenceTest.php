<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class MedicationLogAndAdherenceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_logging_doses_updates_adherence_percent(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($user->tenant)->create();
        $medication = Medication::factory()->forPatient($patient)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $doseOne = now()->subHours(8)->toIso8601String();
        $doseTwo = now()->subHours(4)->toIso8601String();
        $doseThree = now()->subHour()->toIso8601String();

        $this->postJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}/logs", [
            'status' => MedicationLog::STATUS_TAKEN,
            'scheduled_for' => $doseOne,
        ])->assertCreated();

        $this->postJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}/logs", [
            'status' => MedicationLog::STATUS_TAKEN,
            'scheduled_for' => $doseTwo,
        ])->assertCreated();

        $this->postJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}/logs", [
            'status' => MedicationLog::STATUS_MISSED,
            'scheduled_for' => $doseThree,
        ])->assertCreated();

        $this->getJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}/logs")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}/adherence")
            ->assertOk()
            ->assertJsonPath('data.taken', 2)
            ->assertJsonPath('data.missed', 1)
            ->assertJsonPath('data.skipped', 0)
            ->assertJsonPath('data.adherence_percent', 66.7);
    }

    public function test_idempotent_log_replay_does_not_duplicate(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($user->tenant)->create();
        $medication = Medication::factory()->forPatient($patient)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $payload = [
            'status' => MedicationLog::STATUS_TAKEN,
            'scheduled_for' => now()->toIso8601String(),
            'idempotency_key' => 'dose-abc',
        ];

        $first = $this->postJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}/logs", $payload)
            ->assertCreated();

        $second = $this->postJson("/api/v1/patients/{$patient->id}/medications/{$medication->id}/logs", $payload)
            ->assertCreated();

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertDatabaseCount('medication_logs', 1);
    }
}
