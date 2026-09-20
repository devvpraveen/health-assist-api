<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ClinicalAssessmentTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_assessment_crud_and_transition_to_approved(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/patients/'.$patient->id.'/assessments', [
            'template_key' => 'mobility',
            'assessed_at' => '2026-09-01T10:00:00Z',
            'chief_complaint' => 'Knee pain',
            'findings' => ['pain_score' => 6, 'rom' => 90],
            'summary' => 'Limited flexion',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.template_key', 'mobility')
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_DRAFT)
            ->assertJsonPath('data.source', ClinicalDocumentWorkflow::SOURCE_CLINICIAN)
            ->assertJsonPath('data.authored_by_user_id', $user->id);

        $assessmentId = $create->json('data.id');

        $this->getJson('/api/v1/patients/'.$patient->id.'/assessments')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/v1/patients/'.$patient->id.'/assessments/'.$assessmentId, [
            'summary' => 'Updated summary',
        ])->assertOk()
            ->assertJsonPath('data.summary', 'Updated summary');

        $this->postJson('/api/v1/patients/'.$patient->id.'/assessments/'.$assessmentId.'/transition', [
            'status' => ClinicalDocumentWorkflow::STATUS_CLINICIAN_REVIEW,
        ])->assertOk()
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_CLINICIAN_REVIEW);

        $approve = $this->postJson('/api/v1/patients/'.$patient->id.'/assessments/'.$assessmentId.'/transition', [
            'status' => ClinicalDocumentWorkflow::STATUS_APPROVED,
        ]);

        $approve->assertOk()
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_APPROVED)
            ->assertJsonPath('data.approved_by_user_id', $user->id);

        $this->assertNotNull($approve->json('data.approved_at'));

        $this->putJson('/api/v1/patients/'.$patient->id.'/assessments/'.$assessmentId, [
            'summary' => 'Should fail',
        ])->assertForbidden();
    }
}
