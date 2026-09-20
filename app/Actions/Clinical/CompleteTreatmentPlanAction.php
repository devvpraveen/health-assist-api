<?php

namespace App\Actions\Clinical;

use App\Events\TreatmentCompleted;
use App\Models\ClinicalTreatmentPlan;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompleteTreatmentPlanAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    public function handle(ClinicalTreatmentPlan $plan): ClinicalTreatmentPlan
    {
        return DB::transaction(function () use ($plan): ClinicalTreatmentPlan {
            $plan = ClinicalTreatmentPlan::query()->lockForUpdate()->findOrFail($plan->id);

            $plan->update([
                'status' => ClinicalDocumentWorkflow::STATUS_APPROVED,
                'approved_by_user_id' => Auth::id(),
                'approved_at' => now(),
                'end_date' => $plan->end_date ?? now()->toDateString(),
            ]);

            $plan->load('patient');

            $this->timelineRecorder->record(
                $plan->patient,
                'clinical.treatment_plan.completed',
                'Treatment plan completed',
                subject: $plan,
                meta: [
                    'patient_uuid' => $plan->patient->uuid,
                    'treatment_plan_uuid' => $plan->uuid,
                ],
            );

            $this->auditLogger->log('clinical.treatment_plan.completed', $plan, [
                'patient_uuid' => $plan->patient->uuid,
                'treatment_plan_uuid' => $plan->uuid,
            ]);

            event(new TreatmentCompleted($plan));

            return $plan->fresh();
        });
    }
}
