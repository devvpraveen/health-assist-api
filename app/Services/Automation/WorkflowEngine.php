<?php

namespace App\Services\Automation;

use App\Jobs\Automation\AdvanceAutomationWorkflowRunJob;
use App\Models\AutomationWorkflow;
use App\Models\AutomationWorkflowRun;
use App\Models\AutomationWorkflowRunStep;
use App\Models\AutomationWorkflowVersion;
use App\Models\Patient;
use App\Services\AI\Learning\LearningEngine;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Safe, allowlisted workflow runner. No eval / arbitrary code from configuration.
 */
class WorkflowEngine
{
    public const ACTIONS = [
        'noop',
        'log',
        'patient_timeline',
        'learning_signal',
    ];

    public const STEP_TYPES = [
        'condition',
        'delay',
        'action',
    ];

    public function __construct(
        private PatientTimelineRecorder $timeline,
        private LearningEngine $learning,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return list<AutomationWorkflowRun>
     */
    public function dispatch(string $trigger, int $tenantId, array $context = []): array
    {
        $workflows = AutomationWorkflow::query()
            ->with('activeVersion')
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->where('status', AutomationWorkflow::STATUS_PUBLISHED)
            ->whereNotNull('active_version_id')
            ->where(function ($q) use ($tenantId): void {
                $q->where('owner_key', 'platform')
                    ->orWhere('owner_key', 'tenant:'.$tenantId);
            })
            ->get();

        // Tenant overrides: if both platform + tenant share a key, prefer tenant.
        $byKey = [];
        foreach ($workflows as $workflow) {
            $existing = $byKey[$workflow->key] ?? null;
            if ($existing === null || $workflow->owner_key === 'tenant:'.$tenantId) {
                $byKey[$workflow->key] = $workflow;
            }
        }

        $runs = [];
        foreach ($byKey as $workflow) {
            $runs[] = $this->startRun($workflow, $tenantId, $trigger, $context);
        }

        return $runs;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function startRun(
        AutomationWorkflow $workflow,
        int $tenantId,
        string $trigger,
        array $context = [],
    ): AutomationWorkflowRun {
        $version = $workflow->activeVersion;
        if ($version === null) {
            throw new \RuntimeException('Workflow has no active version.');
        }

        $this->assertStepsSafe($version->steps ?? []);

        $run = DB::transaction(function () use ($workflow, $version, $tenantId, $trigger, $context): AutomationWorkflowRun {
            return AutomationWorkflowRun::query()->create([
                'tenant_id' => $tenantId,
                'workflow_id' => $workflow->id,
                'workflow_version_id' => $version->id,
                'trigger' => $trigger,
                'status' => AutomationWorkflowRun::STATUS_PENDING,
                'current_step' => 0,
                'context' => $context,
                'started_at' => now(),
            ]);
        });

        AdvanceAutomationWorkflowRunJob::dispatch($run->id);

        return $run;
    }

    public function advance(AutomationWorkflowRun $run): void
    {
        $run->loadMissing(['version', 'workflow']);

        if (in_array($run->status, [
            AutomationWorkflowRun::STATUS_COMPLETED,
            AutomationWorkflowRun::STATUS_FAILED,
            AutomationWorkflowRun::STATUS_CANCELLED,
            AutomationWorkflowRun::STATUS_SKIPPED,
        ], true)) {
            return;
        }

        $steps = $run->version?->steps ?? [];
        $index = (int) $run->current_step;

        if ($index >= count($steps)) {
            $run->forceFill([
                'status' => AutomationWorkflowRun::STATUS_COMPLETED,
                'finished_at' => now(),
            ])->save();

            return;
        }

        $step = is_array($steps[$index] ?? null) ? $steps[$index] : [];
        $type = (string) ($step['type'] ?? '');

        $run->forceFill(['status' => AutomationWorkflowRun::STATUS_RUNNING])->save();

        $stepRow = AutomationWorkflowRunStep::query()->create([
            'run_id' => $run->id,
            'step_index' => $index,
            'step_type' => $type !== '' ? $type : 'unknown',
            'status' => AutomationWorkflowRunStep::STATUS_WAITING,
            'input' => $step,
            'started_at' => now(),
        ]);

        try {
            if (! in_array($type, self::STEP_TYPES, true)) {
                throw new \RuntimeException("Unsafe or unknown step type [{$type}].");
            }

            $context = $run->context ?? [];

            if ($type === 'condition') {
                $ok = $this->evaluateCondition($step, $context);
                $stepRow->forceFill([
                    'status' => $ok ? AutomationWorkflowRunStep::STATUS_COMPLETED : AutomationWorkflowRunStep::STATUS_SKIPPED,
                    'output' => ['matched' => $ok],
                    'finished_at' => now(),
                ])->save();

                if (! $ok) {
                    $run->forceFill([
                        'status' => AutomationWorkflowRun::STATUS_SKIPPED,
                        'current_step' => $index + 1,
                        'finished_at' => now(),
                    ])->save();

                    return;
                }

                $run->forceFill(['current_step' => $index + 1])->save();
                AdvanceAutomationWorkflowRunJob::dispatch($run->id);

                return;
            }

            if ($type === 'delay') {
                $seconds = max(0, (int) ($step['seconds'] ?? 0));
                $stepRow->forceFill([
                    'status' => AutomationWorkflowRunStep::STATUS_COMPLETED,
                    'output' => ['seconds' => $seconds],
                    'finished_at' => now(),
                ])->save();

                $run->forceFill([
                    'status' => AutomationWorkflowRun::STATUS_WAITING,
                    'current_step' => $index + 1,
                ])->save();

                if ($seconds > 0) {
                    AdvanceAutomationWorkflowRunJob::dispatch($run->id)->delay(now()->addSeconds($seconds));
                } else {
                    AdvanceAutomationWorkflowRunJob::dispatch($run->id);
                }

                return;
            }

            // action
            $output = $this->executeAction($step, $context, $run);
            $stepRow->forceFill([
                'status' => AutomationWorkflowRunStep::STATUS_COMPLETED,
                'output' => $output,
                'finished_at' => now(),
            ])->save();

            $run->forceFill([
                'context' => $context,
                'current_step' => $index + 1,
                'status' => AutomationWorkflowRun::STATUS_PENDING,
            ])->save();

            AdvanceAutomationWorkflowRunJob::dispatch($run->id);
        } catch (Throwable $e) {
            $stepRow->forceFill([
                'status' => AutomationWorkflowRunStep::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ])->save();

            $run->forceFill([
                'status' => AutomationWorkflowRun::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ])->save();
        }
    }

    /**
     * @param  list<array<string, mixed>>|array<int, mixed>  $steps
     */
    public function assertStepsSafe(array $steps): void
    {
        foreach ($steps as $step) {
            if (! is_array($step)) {
                throw new \InvalidArgumentException('Each workflow step must be an object.');
            }
            $type = (string) ($step['type'] ?? '');
            if (! in_array($type, self::STEP_TYPES, true)) {
                throw new \InvalidArgumentException("Unknown step type [{$type}].");
            }
            if ($type === 'action') {
                $action = (string) ($step['action'] ?? '');
                if (! in_array($action, self::ACTIONS, true)) {
                    throw new \InvalidArgumentException("Action [{$action}] is not allowlisted.");
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $context
     */
    private function evaluateCondition(array $step, array $context): bool
    {
        $op = (string) ($step['op'] ?? 'equals');
        $path = (string) ($step['path'] ?? '');
        $expected = $step['value'] ?? null;
        $actual = $path === '' ? null : Arr::get($context, $path);

        return match ($op) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'present' => $actual !== null && $actual !== '',
            'missing' => $actual === null || $actual === '',
            'in' => is_array($expected) && in_array($actual, $expected, false),
            default => throw new \RuntimeException("Unknown condition op [{$op}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $step
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function executeAction(array $step, array &$context, AutomationWorkflowRun $run): array
    {
        $action = (string) ($step['action'] ?? 'noop');
        $params = is_array($step['params'] ?? null) ? $step['params'] : [];

        return match ($action) {
            'noop' => ['ok' => true],
            'log' => $this->actionLog($params, $context, $run),
            'patient_timeline' => $this->actionTimeline($params, $context, $run),
            'learning_signal' => $this->actionLearningSignal($params, $context, $run),
            default => throw new \RuntimeException("Action [{$action}] is not allowlisted."),
        };
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function actionLog(array $params, array $context, AutomationWorkflowRun $run): array
    {
        $message = (string) ($params['message'] ?? 'workflow.step');
        Log::info('[automation.workflow] '.$message, [
            'run_id' => $run->id,
            'workflow_id' => $run->workflow_id,
            'trigger' => $run->trigger,
            'context_keys' => array_keys($context),
        ]);

        return ['logged' => true, 'message' => $message];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function actionTimeline(array $params, array $context, AutomationWorkflowRun $run): array
    {
        $patientId = (int) ($context['patient_id'] ?? 0);
        if ($patientId <= 0) {
            throw new \RuntimeException('patient_timeline requires context.patient_id.');
        }

        $patient = Patient::query()->withoutGlobalScopes()->find($patientId);
        if ($patient === null || (int) $patient->tenant_id !== (int) $run->tenant_id) {
            throw new \RuntimeException('Patient not found for workflow timeline action.');
        }

        $event = $this->timeline->record(
            $patient,
            (string) ($params['event'] ?? 'workflow.action'),
            (string) ($params['title'] ?? 'Workflow action'),
            isset($params['description']) ? (string) $params['description'] : null,
            subject: $run,
            meta: [
                'workflow_id' => $run->workflow_id,
                'run_uuid' => $run->uuid,
                'trigger' => $run->trigger,
            ],
        );

        return ['timeline_event_id' => $event->id];
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function actionLearningSignal(array $params, array $context, AutomationWorkflowRun $run): array
    {
        $signalType = (string) ($params['signal_type'] ?? 'followup_completed');
        $agent = (string) ($params['agent'] ?? 'follow_up');

        $signal = $this->learning->recordSignal(
            tenantId: (int) $run->tenant_id,
            agent: $agent,
            signalType: $signalType,
            source: 'automation_workflow',
            patientId: isset($context['patient_id']) ? (int) $context['patient_id'] : null,
            subject: $run,
            payload: [
                'workflow_id' => $run->workflow_id,
                'run_uuid' => $run->uuid,
                'trigger' => $run->trigger,
                'tool_chain' => ['automation_workflow', $run->trigger],
            ],
        );

        return ['learning_signal_id' => $signal->id];
    }
}
