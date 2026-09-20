<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalExercisePlan;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateClinicalExercisePlanAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): ClinicalExercisePlan
    {
        return DB::transaction(function () use ($patient, $data): ClinicalExercisePlan {
            $plan = ClinicalExercisePlan::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? ClinicalExercisePlan::STATUS_ACTIVE,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->timelineRecorder->record(
                $patient,
                'clinical.exercise_plan.created',
                'Exercise plan created',
                subject: $plan,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'exercise_plan_uuid' => $plan->uuid,
                ],
            );

            $this->auditLogger->log('clinical.exercise_plan.created', $plan, [
                'patient_uuid' => $patient->uuid,
                'exercise_plan_uuid' => $plan->uuid,
            ]);

            return $plan;
        });
    }
}
