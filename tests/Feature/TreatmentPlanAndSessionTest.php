<?php

namespace Tests\Feature;

use App\Events\TreatmentCompleted;
use App\Models\Patient;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class TreatmentPlanAndSessionTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_treatment_plan_and_session_complete(): void
    {
        Event::fake([TreatmentCompleted::class]);

        [$user, , $tenant] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $plan = $this->postJson('/api/v1/patients/'.$patient->id.'/treatment-plans', [
            'title' => 'ACL rehab',
            'diagnosis_summary' => 'Post-op ACL',
            'goals' => ['Full ROM', 'Return to sport'],
            'frequency' => '3x/week',
            'duration_weeks' => 12,
            'start_date' => '2026-09-01',
        ]);

        $plan->assertCreated()
            ->assertJsonPath('data.title', 'ACL rehab')
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_DRAFT);

        $planId = $plan->json('data.id');

        $session = $this->postJson('/api/v1/patients/'.$patient->id.'/treatment-plans/'.$planId.'/sessions', [
            'session_at' => '2026-09-03T09:00:00Z',
            'modality' => 'exercise',
            'interventions' => 'Quad sets, gait training',
            'duration_minutes' => 45,
        ]);

        $session->assertCreated()
            ->assertJsonPath('data.treatment_plan_id', $planId)
            ->assertJsonPath('data.status', 'scheduled');

        $sessionId = $session->json('data.id');

        $this->putJson('/api/v1/patients/'.$patient->id.'/treatment-sessions/'.$sessionId, [
            'status' => 'completed',
            'patient_response' => 'Tolerated well',
        ])->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->postJson('/api/v1/patients/'.$patient->id.'/treatment-plans/'.$planId.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', ClinicalDocumentWorkflow::STATUS_APPROVED);

        Event::assertDispatched(TreatmentCompleted::class);
    }
}
