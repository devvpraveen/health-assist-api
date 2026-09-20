<?php

namespace Tests\Feature;

use App\Events\DischargeCreated;
use App\Models\Patient;
use App\Models\PatientTimelineEvent;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class DischargeSummaryTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_discharge_create_approve_timeline_and_event(): void
    {
        Event::fake([DischargeCreated::class]);

        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/patients/'.$patient->id.'/discharge-summaries', [
            'discharged_at' => '2026-09-10T16:00:00Z',
            'reason' => 'Goals met',
            'initial_condition' => 'Acute ankle sprain',
            'treatment_provided' => 'Manual therapy and HEP',
            'progress_summary' => 'Full return to ADLs',
            'current_status' => 'Independent',
            'home_program' => 'Continue ankle pumps',
            'follow_up' => 'PRN',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.reason', 'Goals met')
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_DRAFT);

        Event::assertDispatched(DischargeCreated::class);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'clinical.discharge.created',
        ]);

        $summaryId = $create->json('data.id');

        $this->postJson('/api/v1/patients/'.$patient->id.'/discharge-summaries/'.$summaryId.'/transition', [
            'status' => ClinicalDocumentWorkflow::STATUS_CLINICIAN_REVIEW,
        ])->assertOk();

        $this->postJson('/api/v1/patients/'.$patient->id.'/discharge-summaries/'.$summaryId.'/transition', [
            'status' => ClinicalDocumentWorkflow::STATUS_APPROVED,
        ])->assertOk()
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_APPROVED)
            ->assertJsonPath('data.approved_by_user_id', $user->id);

        Event::assertDispatchedTimes(DischargeCreated::class, 2);

        $this->assertTrue(
            PatientTimelineEvent::query()
                ->where('patient_id', $patient->id)
                ->where('event_type', 'clinical.discharge.transitioned')
                ->exists()
        );
    }
}
