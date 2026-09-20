<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientDocument;
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

class ReportAnalysisPipelineTest extends TestCase
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

    public function test_analyze_document_pipeline_reaches_awaiting_review_with_facts_and_versions(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $upload = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => UploadedFile::fake()->create('lab-report.pdf', 100, 'application/pdf'),
            'category' => 'laboratory',
        ], ['Accept' => 'application/json'])->assertCreated();

        $documentId = $upload->json('data.id');

        $response = $this->postJson(
            "/api/v1/patients/{$patient->id}/documents/{$documentId}/analyze",
            ['report_type' => 'lab'],
        )->assertCreated()
            ->assertJsonPath('data.status', ReportAnalysis::STATUS_AWAITING_REVIEW)
            ->assertJsonPath('data.report_type', 'lab')
            ->assertJsonStructure([
                'data' => [
                    'extracted_facts' => ['tests'],
                    'interpretation' => [
                        'possible_interpretation',
                        'uncertainty',
                        'items_requiring_review',
                        'requires_clinician_review',
                    ],
                    'patient_explanation',
                    'reference_range_findings',
                    'safety_level',
                    'versions',
                ],
            ]);

        $analysisId = $response->json('data.id');
        $this->assertNotEmpty($response->json('data.extracted_facts.tests'));
        $this->assertNotEmpty($response->json('data.patient_explanation'));
        $this->assertTrue($response->json('data.interpretation.requires_clinician_review'));

        $kinds = ReportAnalysisVersion::query()
            ->where('report_analysis_id', $analysisId)
            ->pluck('kind')
            ->all();

        $this->assertContains(ReportAnalysisVersion::KIND_OCR, $kinds);
        $this->assertContains(ReportAnalysisVersion::KIND_EXTRACTION, $kinds);
        $this->assertContains(ReportAnalysisVersion::KIND_INTERPRETATION, $kinds);
        $this->assertContains(ReportAnalysisVersion::KIND_PATIENT_EXPLANATION, $kinds);
        $this->assertNotContains(ReportAnalysisVersion::KIND_FINAL, $kinds);

        $document = PatientDocument::query()->findOrFail($documentId);
        $this->assertTrue($document->reportAnalyses()->whereKey($analysisId)->exists());

        $this->getJson("/api/v1/patients/{$patient->id}/report-analyses")
            ->assertOk()
            ->assertJsonPath('data.0.id', $analysisId);

        $this->getJson('/api/v1/report-analyses?status=awaiting_review')
            ->assertOk()
            ->assertJsonPath('data.0.id', $analysisId);
    }
}
