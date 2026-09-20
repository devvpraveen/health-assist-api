<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PatientDocumentTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_upload_show_download_and_delete_document(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $file = UploadedFile::fake()->create('lab-report.pdf', 100, 'application/pdf');

        $upload = $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => $file,
            'category' => 'laboratory',
        ], [
            'Accept' => 'application/json',
        ]);

        $upload->assertCreated()
            ->assertJsonPath('data.original_filename', 'lab-report.pdf')
            ->assertJsonPath('data.mime_type', 'application/pdf')
            ->assertJsonMissingPath('data.path');

        $documentId = $upload->json('data.id');
        $document = PatientDocument::query()->findOrFail($documentId);

        Storage::disk('local')->assertExists($document->path);

        $show = $this->getJson('/api/v1/patients/'.$patient->id.'/documents/'.$documentId);

        $show->assertOk()
            ->assertJsonPath('data.uuid', $document->uuid)
            ->assertJsonMissingPath('data.path');

        $this->get('/api/v1/patients/'.$patient->id.'/documents/'.$documentId.'/download')
            ->assertOk();

        $this->deleteJson('/api/v1/patients/'.$patient->id.'/documents/'.$documentId)
            ->assertNoContent();

        $this->assertDatabaseMissing('patient_documents', ['id' => $documentId]);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_rejects_invalid_mime_type(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $this->post('/api/v1/patients/'.$patient->id.'/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }
}
