<?php

namespace Tests\Feature;

use App\Models\Patient;
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

class DistinguishesFactsFromInterpretationTest extends TestCase
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

    public function test_response_keeps_extracted_facts_separate_from_interpretation(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $documentId = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => UploadedFile::fake()->create('lab-report.pdf', 60, 'application/pdf'),
            'category' => 'laboratory',
        ], ['Accept' => 'application/json'])->json('data.id');

        $data = $this->postJson(
            "/api/v1/patients/{$patient->id}/documents/{$documentId}/analyze"
        )->assertCreated()->json('data');

        $this->assertArrayHasKey('extracted_facts', $data);
        $this->assertArrayHasKey('interpretation', $data);
        $this->assertArrayHasKey('reference_range_findings', $data);
        $this->assertArrayHasKey('patient_explanation', $data);

        $this->assertArrayHasKey('tests', $data['extracted_facts']);
        $this->assertArrayHasKey('possible_interpretation', $data['interpretation']);
        $this->assertArrayHasKey('uncertainty', $data['interpretation']);
        $this->assertArrayHasKey('items_requiring_review', $data['interpretation']);
        $this->assertTrue($data['interpretation']['requires_clinician_review']);

        $this->assertArrayNotHasKey('possible_interpretation', $data['extracted_facts']);
        $this->assertNotSame($data['extracted_facts'], $data['interpretation']);
    }
}
