<?php

namespace Database\Seeders;

use App\Actions\Automation\PublishAutomationWorkflowAction;
use App\Models\AutomationWorkflow;
use App\Models\AutomationWorkflowVersion;
use Illuminate\Database\Seeder;

class AutomationWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $workflow = AutomationWorkflow::query()->updateOrCreate(
            ['owner_key' => 'platform', 'key' => 'appointment_completed_followup'],
            [
                'tenant_id' => null,
                'name' => 'Appointment completed follow-up',
                'description' => 'Timeline note + learning signal when an appointment is completed.',
                'trigger' => 'appointment.completed',
                'module_key' => 'appointments',
                'status' => AutomationWorkflow::STATUS_DRAFT,
                'is_active' => false,
            ],
        );

        $version = AutomationWorkflowVersion::query()->updateOrCreate(
            ['workflow_id' => $workflow->id, 'version' => 1],
            [
                'label' => 'v1',
                'status' => AutomationWorkflowVersion::STATUS_DRAFT,
                'steps' => [
                    [
                        'type' => 'condition',
                        'op' => 'present',
                        'path' => 'patient_id',
                    ],
                    [
                        'type' => 'action',
                        'action' => 'patient_timeline',
                        'params' => [
                            'event' => 'workflow.appointment_completed',
                            'title' => 'Appointment completed follow-up',
                            'description' => 'Automation workflow recorded completion.',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => 'learning_signal',
                        'params' => [
                            'signal_type' => 'followup_completed',
                            'agent' => 'follow_up',
                        ],
                    ],
                    [
                        'type' => 'action',
                        'action' => 'log',
                        'params' => [
                            'message' => 'appointment.completed workflow finished',
                        ],
                    ],
                ],
            ],
        );

        if ($workflow->active_version_id === null) {
            app(PublishAutomationWorkflowAction::class)->handle($workflow, $version);
        }
    }
}
