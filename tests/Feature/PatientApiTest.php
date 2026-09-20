<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_create_list_show_and_update_patient(): void
    {
        [$user] = $this->createTenantUserWithOrg('Clinic A');

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/patients', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phone' => '+15551234567',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.first_name', 'Ada')
            ->assertJsonPath('data.last_name', 'Lovelace')
            ->assertJsonPath('data.email', 'ada@example.com');

        $patientId = $create->json('data.id');

        $this->getJson('/api/v1/patients')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $patientId);

        $this->getJson('/api/v1/patients/'.$patientId)
            ->assertOk()
            ->assertJsonPath('data.uuid', $create->json('data.uuid'));

        $this->putJson('/api/v1/patients/'.$patientId, [
            'first_name' => 'Augusta',
            'city' => 'London',
        ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Augusta')
            ->assertJsonPath('data.city', 'London');

        $this->assertDatabaseHas('patients', [
            'id' => $patientId,
            'first_name' => 'Augusta',
            'city' => 'London',
        ]);
    }

    public function test_create_patient_requires_names(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/patients', [
            'email' => 'missing-names@example.com',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_can_delete_patient(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->deleteJson('/api/v1/patients/'.$patient->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }
}
