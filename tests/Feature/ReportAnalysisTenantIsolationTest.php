<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\ReportAnalysis;
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

class ReportAnalysisTenantIsolationTest extends TestCase
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

    public function test_tenant_cannot_view_or_analyze_other_tenant_reports(): void
    {
        [$userA, , $tenantA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        $patientA = Patient::factory()->forTenant($tenantA)->create();
        $patientB = Patient::factory()->forTenant($tenantB)->create();

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $documentAId = $this->post('/api/v1/patients/'.$patientA->id.'/documents', [
            'file' => UploadedFile::fake()->create('lab-report.pdf', 40, 'application/pdf'),
        ], ['Accept' => 'application/json'])->json('data.id');

        $analysisAId = $this->postJson(
            "/api/v1/patients/{$patientA->id}/documents/{$documentAId}/analyze"
        )->assertCreated()->json('data.id');

        Sanctum::actingAs($userB);
        TenantContext::set($userB->tenant_id);

        $this->getJson("/api/v1/patients/{$patientA->id}/report-analyses/{$analysisAId}")
            ->assertNotFound();

        $this->getJson('/api/v1/report-analyses')
            ->assertOk()
            ->assertJsonMissing(['id' => $analysisAId]);

        $documentB = PatientDocument::factory()->forPatient($patientB)->create();
        Storage::disk('local')->put($documentB->path, 'fake-pdf');

        $this->postJson("/api/v1/patients/{$patientA->id}/documents/{$documentAId}/analyze")
            ->assertNotFound();

        $this->assertSame(0, ReportAnalysis::query()->where('tenant_id', $tenantB->id)->whereKey($analysisAId)->count());
        $this->assertSame(1, ReportAnalysis::withoutGlobalScopes()->whereKey($analysisAId)->where('tenant_id', $tenantA->id)->count());
    }
}
