<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalTreatmentPlan;
use App\Services\AuditLogger;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Support\Facades\DB;

class UpdateClinicalTreatmentPlanAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ClinicalTreatmentPlan $plan, array $data): ClinicalTreatmentPlan
    {
        return DB::transaction(function () use ($plan, $data): ClinicalTreatmentPlan {
            ClinicalDocumentWorkflow::assertEditable($plan);

            $plan->update(collect($data)->only([
                'provider_id',
                'clinic_id',
                'title',
                'diagnosis_summary',
                'goals',
                'frequency',
                'duration_weeks',
                'start_date',
                'end_date',
                'reassessment_date',
                'home_program_notes',
                'source',
            ])->all());

            $this->auditLogger->log('clinical.treatment_plan.updated', $plan, [
                'treatment_plan_uuid' => $plan->uuid,
            ]);

            return $plan->fresh();
        });
    }
}
