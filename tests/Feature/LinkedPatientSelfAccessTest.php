<?php

namespace Tests\Feature;

use App\Models\HealthRecord;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class LinkedPatientSelfAccessTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_linked_patient_user_can_list_self_and_health_records(): void
    {
        [$admin, , $tenant] = $this->createTenantUserWithOrg('Clinic Self Access');

        $patientUser = User::factory()->forTenant($tenant)->create([
            'email' => 'self-patient@example.com',
            'password' => Hash::make('password'),
        ]);

        $patient = Patient::factory()->forTenant($tenant)->create([
            'user_id' => $patientUser->id,
            'first_name' => 'Self',
            'last_name' => 'Patient',
            'email' => 'self-patient@example.com',
        ]);

        HealthRecord::factory()->forPatient($patient)->create([
            'title' => 'Self record',
            'category' => 'consultation_note',
        ]);

        Medication::factory()->forPatient($patient)->create([
            'name' => 'Demo Med',
        ]);

        // Unrelated patient should not appear for linked user.
        Patient::factory()->forTenant($tenant)->create([
            'first_name' => 'Other',
            'last_name' => 'Person',
        ]);

        Sanctum::actingAs($patientUser);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/patients')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $patient->id);

        $this->getJson("/api/v1/patients/{$patient->id}/health-records")
            ->assertOk()
            ->assertJsonFragment(['title' => 'Self record']);

        $this->getJson("/api/v1/patients/{$patient->id}/medications")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Demo Med']);

        // Staff can still see everyone.
        Sanctum::actingAs($admin);
        TenantContext::set($tenant->id);
        $this->getJson('/api/v1/patients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
