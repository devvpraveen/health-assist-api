<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalTreatmentSession;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;

class UpdateClinicalTreatmentSessionAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ClinicalTreatmentSession $session, array $data): ClinicalTreatmentSession
    {
        return DB::transaction(function () use ($session, $data): ClinicalTreatmentSession {
            $previousStatus = $session->status;

            $session->update(collect($data)->only([
                'treatment_plan_id',
                'appointment_id',
                'provider_id',
                'session_at',
                'modality',
                'interventions',
                'patient_response',
                'duration_minutes',
                'status',
            ])->all());

            $session->load('patient');

            if (($data['status'] ?? null) === ClinicalTreatmentSession::STATUS_COMPLETED
                && $previousStatus !== ClinicalTreatmentSession::STATUS_COMPLETED) {
                $this->timelineRecorder->record(
                    $session->patient,
                    'clinical.treatment_session.completed',
                    'Treatment session completed',
                    subject: $session,
                    meta: [
                        'patient_uuid' => $session->patient->uuid,
                        'session_uuid' => $session->uuid,
                    ],
                );
            }

            $this->auditLogger->log('clinical.treatment_session.updated', $session, [
                'session_uuid' => $session->uuid,
                'status' => $session->status,
            ]);

            return $session->fresh();
        });
    }
}
