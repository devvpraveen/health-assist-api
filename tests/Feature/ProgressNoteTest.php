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

class ProgressNoteTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_progress_note_crud(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/patients/'.$patient->id.'/progress-notes', [
            'noted_at' => '2026-09-04T14:00:00Z',
            'note' => 'Pain decreasing with HEP adherence',
            'measurements' => ['pain_score' => 3, 'function' => 7],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.note', 'Pain decreasing with HEP adherence')
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_DRAFT)
            ->assertJsonPath('data.measurements.pain_score', 3);

        $noteId = $create->json('data.id');

        $this->getJson('/api/v1/patients/'.$patient->id.'/progress-notes')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/v1/patients/'.$patient->id.'/progress-notes/'.$noteId, [
            'note' => 'Updated progress note',
        ])->assertOk()
            ->assertJsonPath('data.note', 'Updated progress note');

        $this->deleteJson('/api/v1/patients/'.$patient->id.'/progress-notes/'.$noteId)
            ->assertNoContent();
    }
}
