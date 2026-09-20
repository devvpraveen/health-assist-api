<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Provider;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class SoapNoteTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_soap_note_create_approve_locks_updates(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();
        $provider = Provider::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/patients/'.$patient->id.'/soap-notes', [
            'provider_id' => $provider->id,
            'session_date' => '2026-09-02T11:00:00Z',
            'subjective' => 'Reports soreness',
            'objective' => 'Mild swelling',
            'assessment' => 'Improving',
            'plan' => 'Continue HEP',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.subjective', 'Reports soreness')
            ->assertJsonPath('data.objective', 'Mild swelling')
            ->assertJsonPath('data.assessment', 'Improving')
            ->assertJsonPath('data.plan', 'Continue HEP')
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_DRAFT);

        $noteId = $create->json('data.id');

        $this->postJson('/api/v1/patients/'.$patient->id.'/soap-notes/'.$noteId.'/transition', [
            'status' => ClinicalDocumentWorkflow::STATUS_CLINICIAN_REVIEW,
        ])->assertOk();

        $this->postJson('/api/v1/patients/'.$patient->id.'/soap-notes/'.$noteId.'/transition', [
            'status' => ClinicalDocumentWorkflow::STATUS_APPROVED,
        ])->assertOk()
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_APPROVED)
            ->assertJsonPath('data.approved_by_user_id', $user->id);

        $this->putJson('/api/v1/patients/'.$patient->id.'/soap-notes/'.$noteId, [
            'plan' => 'Cannot change',
        ])->assertForbidden();
    }
}
