<?php

namespace Tests\Feature;

use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PatientTimelineTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_timeline_events_appear_after_patient_create_and_document_upload(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/patients', [
            'first_name' => 'Timeline',
            'last_name' => 'Patient',
        ])->assertCreated();

        $patientId = $create->json('data.id');

        $this->getJson('/api/v1/patients/'.$patientId.'/timeline')
            ->assertOk()
            ->assertJsonFragment(['event_type' => 'patient.created']);

        $file = UploadedFile::fake()->create('scan.pdf', 50, 'application/pdf');

        $this->post('/api/v1/patients/'.$patientId.'/documents', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ])->assertCreated();

        $timeline = $this->getJson('/api/v1/patients/'.$patientId.'/timeline')
            ->assertOk();

        $eventTypes = collect($timeline->json('data'))->pluck('event_type')->all();

        $this->assertContains('patient.created', $eventTypes);
        $this->assertContains('document.uploaded', $eventTypes);
    }
}
