<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class EmergencyContactTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_emergency_contact_crud_and_primary_flag(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $first = $this->postJson('/api/v1/patients/'.$patient->id.'/emergency-contacts', [
            'name' => 'Primary Contact',
            'relationship' => 'spouse',
            'phone' => '+15550001111',
            'is_primary' => true,
        ])->assertCreated()
            ->assertJsonPath('data.is_primary', true);

        $second = $this->postJson('/api/v1/patients/'.$patient->id.'/emergency-contacts', [
            'name' => 'Secondary Contact',
            'relationship' => 'parent',
            'phone' => '+15550002222',
            'is_primary' => true,
        ])->assertCreated()
            ->assertJsonPath('data.is_primary', true);

        $this->assertFalse(
            PatientEmergencyContact::query()->findOrFail($first->json('data.id'))->is_primary
        );
        $this->assertTrue(
            PatientEmergencyContact::query()->findOrFail($second->json('data.id'))->is_primary
        );

        $contactId = $second->json('data.id');

        $this->getJson('/api/v1/patients/'.$patient->id.'/emergency-contacts')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->putJson('/api/v1/patients/'.$patient->id.'/emergency-contacts/'.$contactId, [
            'name' => 'Updated Contact',
            'phone' => '+15550003333',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Contact');

        $this->deleteJson('/api/v1/patients/'.$patient->id.'/emergency-contacts/'.$contactId)
            ->assertNoContent();

        $this->assertDatabaseMissing('patient_emergency_contacts', ['id' => $contactId]);
    }
}
