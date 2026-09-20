<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalTreatmentPlan;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateClinicalTreatmentPlanAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): ClinicalTreatmentPlan
    {
        return DB::transaction(function () use ($patient, $data): ClinicalTreatmentPlan {
            $plan = ClinicalTreatmentPlan::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'provider_id' => $data['provider_id'] ?? null,
                'clinic_id' => $data['clinic_id'] ?? null,
                'title' => $data['title'],
                'diagnosis_summary' => $data['diagnosis_summary'] ?? null,
                'goals' => $data['goals'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'duration_weeks' => $data['duration_weeks'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'reassessment_date' => $data['reassessment_date'] ?? null,
                'home_program_notes' => $data['home_program_notes'] ?? null,
                'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
                'source' => $data['source'] ?? ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
                'authored_by_user_id' => Auth::id(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'clinical.treatment_plan.created',
                'Treatment plan created',
                subject: $plan,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'treatment_plan_uuid' => $plan->uuid,
                ],
            );

            $this->auditLogger->log('clinical.treatment_plan.created', $plan, [
                'patient_uuid' => $patient->uuid,
                'treatment_plan_uuid' => $plan->uuid,
            ]);

            return $plan;
        });
    }
}
