<?php

namespace Tests\Feature;

use App\Events\AppointmentCompleted;
use App\Models\Appointment;
use App\Models\AutomationWorkflow;
use App\Models\AutomationWorkflowRun;
use App\Models\Patient;
use App\Models\PatientTimelineEvent;
use App\Support\TenantContext;
use Database\Seeders\AutomationWorkflowSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AutomationWorkflowTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AutomationWorkflowSeeder::class);
    }

    public function test_rejects_unsafe_action_on_create(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Unsafe Workflow Clinic');
        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->postJson('/api/v1/workflows', [
            'key' => 'bad_exec',
            'name' => 'Bad',
            'trigger' => 'appointment.completed',
            'steps' => [
                ['type' => 'action', 'action' => 'eval', 'params' => ['code' => 'rm -rf /']],
            ],
        ])->assertStatus(422);
    }

    public function test_manual_trigger_runs_allowlisted_steps(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Manual Workflow Clinic');
        $patient = Patient::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $create = $this->postJson('/api/v1/workflows', [
            'key' => 'manual_ping',
            'name' => 'Manual ping',
            'trigger' => 'manual.ping',
            'publish' => true,
            'steps' => [
                ['type' => 'condition', 'op' => 'equals', 'path' => 'flag', 'value' => true],
                [
                    'type' => 'action',
                    'action' => 'patient_timeline',
                    'params' => ['event' => 'workflow.manual', 'title' => 'Manual workflow'],
                ],
                ['type' => 'action', 'action' => 'noop'],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'published');

        $workflowId = $create->json('data.id');

        $this->postJson("/api/v1/workflows/{$workflowId}/trigger", [
            'context' => [
                'flag' => true,
                'patient_id' => $patient->id,
            ],
        ])->assertStatus(202)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'workflow.manual',
        ]);

        $this->getJson('/api/v1/workflow-runs?workflow_id='.$workflowId)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'completed');
    }

    public function test_appointment_completed_event_starts_platform_workflow(): void
    {
        [, , $tenant] = $this->createTenantUserWithOrg('Event Workflow Clinic');
        $patient = Patient::factory()->forTenant($tenant)->create();

        $appointment = Appointment::factory()->forPatient($patient)->create([
            'status' => 'completed',
        ]);

        event(new AppointmentCompleted($appointment));

        $this->assertTrue(
            AutomationWorkflow::query()
                ->where('key', 'appointment_completed_followup')
                ->where('is_active', true)
                ->exists()
        );

        $this->assertDatabaseHas('automation_workflow_runs', [
            'tenant_id' => $tenant->id,
            'trigger' => 'appointment.completed',
            'status' => AutomationWorkflowRun::STATUS_COMPLETED,
        ]);

        $this->assertTrue(
            PatientTimelineEvent::query()
                ->withoutGlobalScopes()
                ->where('patient_id', $patient->id)
                ->where('event_type', 'workflow.appointment_completed')
                ->exists()
        );
    }

    public function test_failed_condition_skips_run(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Skip Workflow Clinic');
        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $create = $this->postJson('/api/v1/workflows', [
            'key' => 'skip_me',
            'name' => 'Skip me',
            'trigger' => 'manual.skip',
            'publish' => true,
            'steps' => [
                ['type' => 'condition', 'op' => 'equals', 'path' => 'ready', 'value' => true],
                ['type' => 'action', 'action' => 'log', 'params' => ['message' => 'should not run']],
            ],
        ])->assertCreated();

        $this->postJson('/api/v1/workflows/'.$create->json('data.id').'/trigger', [
            'context' => ['ready' => false],
        ])
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'skipped');
    }
}
