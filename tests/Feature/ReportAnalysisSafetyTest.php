<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\ReportAnalysis;
use App\Models\SafetyAssessment;
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

class ReportAnalysisSafetyTest extends TestCase
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

    public function test_emergency_keywords_set_safety_level_but_stay_awaiting_review(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $documentId = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => UploadedFile::fake()->create('chest_pain_lab.pdf', 80, 'application/pdf'),
            'category' => 'laboratory',
        ], ['Accept' => 'application/json'])->json('data.id');

        $response = $this->postJson(
            "/api/v1/patients/{$patient->id}/documents/{$documentId}/analyze"
        )->assertCreated()
            ->assertJsonPath('data.status', ReportAnalysis::STATUS_AWAITING_REVIEW)
            ->assertJsonPath('data.safety_level', SafetyAssessment::LEVEL_EMERGENCY);

        $this->assertNotSame(ReportAnalysis::STATUS_APPROVED, $response->json('data.status'));
        $this->assertNotNull($response->json('data.safety_assessment_id'));
        $this->assertTrue($response->json('data.interpretation.requires_clinician_review'));
    }
}
