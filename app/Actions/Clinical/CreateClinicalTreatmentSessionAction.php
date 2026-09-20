<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalTreatmentSession;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateClinicalTreatmentSessionAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): ClinicalTreatmentSession
    {
        return DB::transaction(function () use ($patient, $data): ClinicalTreatmentSession {
            $session = ClinicalTreatmentSession::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'session_at' => $data['session_at'],
                'modality' => $data['modality'] ?? null,
                'interventions' => $data['interventions'] ?? null,
                'patient_response' => $data['patient_response'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'status' => $data['status'] ?? ClinicalTreatmentSession::STATUS_SCHEDULED,
            ]);

            $this->timelineRecorder->record(
                $patient,
                'clinical.treatment_session.created',
                'Treatment session created',
                subject: $session,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'session_uuid' => $session->uuid,
                ],
            );

            $this->auditLogger->log('clinical.treatment_session.created', $session, [
                'patient_uuid' => $patient->uuid,
                'session_uuid' => $session->uuid,
            ]);

            return $session;
        });
    }
}
