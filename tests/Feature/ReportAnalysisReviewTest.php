<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\ReportAnalysis;
use App\Models\ReportAnalysisVersion;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ReportAnalysisReviewTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
        Storage::fake('local');
    }

    public function test_approve_locks_analysis_and_stores_final_version(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $documentId = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => UploadedFile::fake()->create('lab-report.pdf', 50, 'application/pdf'),
            'category' => 'laboratory',
        ], ['Accept' => 'application/json'])->json('data.id');

        $analysisId = $this->postJson(
            "/api/v1/patients/{$patient->id}/documents/{$documentId}/analyze"
        )->assertCreated()->json('data.id');

        $this->postJson("/api/v1/patients/{$patient->id}/report-analyses/{$analysisId}/approve", [
            'clinician_notes' => 'Reviewed and OK to share.',
            'patient_explanation' => 'Clinician-edited patient explanation.',
        ])->assertOk()
            ->assertJsonPath('data.status', ReportAnalysis::STATUS_APPROVED)
            ->assertJsonPath('data.patient_explanation', 'Clinician-edited patient explanation.')
            ->assertJsonPath('data.clinician_notes', 'Reviewed and OK to share.');

        $this->assertDatabaseHas('report_analyses', [
            'id' => $analysisId,
            'status' => ReportAnalysis::STATUS_APPROVED,
            'reviewed_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('report_analysis_versions', [
            'report_analysis_id' => $analysisId,
            'kind' => ReportAnalysisVersion::KIND_FINAL,
            'source' => ReportAnalysisVersion::SOURCE_CLINICIAN,
        ]);

        $this->postJson("/api/v1/patients/{$patient->id}/report-analyses/{$analysisId}/approve", [
            'clinician_notes' => 'again',
        ])->assertUnprocessable();

        $this->postJson("/api/v1/patients/{$patient->id}/report-analyses/{$analysisId}/regenerate-explanation")
            ->assertUnprocessable();
    }

    public function test_reject_analysis(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $documentId = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => UploadedFile::fake()->create('lab-report.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])->json('data.id');

        $analysisId = $this->postJson(
            "/api/v1/patients/{$patient->id}/documents/{$documentId}/analyze"
        )->json('data.id');

        $this->postJson("/api/v1/patients/{$patient->id}/report-analyses/{$analysisId}/reject", [
            'clinician_notes' => 'OCR quality too poor.',
        ])->assertOk()
            ->assertJsonPath('data.status', ReportAnalysis::STATUS_REJECTED)
            ->assertJsonPath('data.clinician_notes', 'OCR quality too poor.');
    }

    public function test_regenerate_explanation_while_awaiting_review(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $documentId = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => UploadedFile::fake()->create('lab-report.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])->json('data.id');

        $analysisId = $this->postJson(
            "/api/v1/patients/{$patient->id}/documents/{$documentId}/analyze"
        )->json('data.id');

        $before = ReportAnalysisVersion::query()
            ->where('report_analysis_id', $analysisId)
            ->where('kind', ReportAnalysisVersion::KIND_PATIENT_EXPLANATION)
            ->count();

        $this->postJson("/api/v1/patients/{$patient->id}/report-analyses/{$analysisId}/regenerate-explanation")
            ->assertOk()
            ->assertJsonPath('data.status', ReportAnalysis::STATUS_AWAITING_REVIEW);

        $this->assertSame(
            $before + 1,
            ReportAnalysisVersion::query()
                ->where('report_analysis_id', $analysisId)
                ->where('kind', ReportAnalysisVersion::KIND_PATIENT_EXPLANATION)
                ->count()
        );
    }
}
