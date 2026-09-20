<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalSoapNote;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateClinicalSoapNoteAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): ClinicalSoapNote
    {
        return DB::transaction(function () use ($patient, $data): ClinicalSoapNote {
            $note = ClinicalSoapNote::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'provider_id' => $data['provider_id'],
                'clinic_id' => $data['clinic_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'assessment_id' => $data['assessment_id'] ?? null,
                'subjective' => $data['subjective'] ?? null,
                'objective' => $data['objective'] ?? null,
                'assessment' => $data['assessment'] ?? null,
                'plan' => $data['plan'] ?? null,
                'session_date' => $data['session_date'],
                'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
                'source' => $data['source'] ?? ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
                'authored_by_user_id' => Auth::id(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'clinical.soap_note.created',
                'SOAP note created',
                subject: $note,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'soap_note_uuid' => $note->uuid,
                ],
            );

            $this->auditLogger->log('clinical.soap_note.created', $note, [
                'patient_uuid' => $patient->uuid,
                'soap_note_uuid' => $note->uuid,
            ]);

            return $note;
        });
    }
}
