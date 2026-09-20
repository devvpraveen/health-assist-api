<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalExercisePlan;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateClinicalExercisePlanAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ClinicalExercisePlan $plan, array $data): ClinicalExercisePlan
    {
        return DB::transaction(function () use ($plan, $data): ClinicalExercisePlan {
            $plan->update(collect($data)->only([
                'treatment_plan_id',
                'provider_id',
                'title',
                'start_date',
                'end_date',
                'status',
                'notes',
            ])->all());

            $this->auditLogger->log('clinical.exercise_plan.updated', $plan, [
                'exercise_plan_uuid' => $plan->uuid,
            ]);

            return $plan->fresh();
        });
    }
}
