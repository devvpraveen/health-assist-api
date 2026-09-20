<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalExerciseLog;
use App\Models\ClinicalExercisePlan;
use App\Models\ClinicalExercisePlanItem;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LogClinicalExerciseAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(
        ClinicalExercisePlan $plan,
        ClinicalExercisePlanItem $item,
        array $data,
    ): ClinicalExerciseLog {
        return DB::transaction(function () use ($plan, $item, $data): ClinicalExerciseLog {
            if ($item->exercise_plan_id !== $plan->id) {
                throw ValidationException::withMessages([
                    'item' => ['Exercise plan item does not belong to this plan.'],
                ]);
            }

            $log = ClinicalExerciseLog::query()->create([
                'tenant_id' => TenantContext::id() ?? $plan->tenant_id,
                'patient_id' => $plan->patient_id,
                'exercise_plan_item_id' => $item->id,
                'performed_at' => $data['performed_at'],
                'result' => $data['result'],
                'notes' => $data['notes'] ?? null,
                'pain_score' => $data['pain_score'] ?? null,
            ]);

            $this->auditLogger->log('clinical.exercise_log.created', $log, [
                'exercise_plan_uuid' => $plan->uuid,
                'exercise_log_uuid' => $log->uuid,
                'result' => $log->result,
            ]);

            return $log;
        });
    }
}
