<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalProgressNote;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateClinicalProgressNoteAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): ClinicalProgressNote
    {
        return DB::transaction(function () use ($patient, $data): ClinicalProgressNote {
            $note = ClinicalProgressNote::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'provider_id' => $data['provider_id'] ?? null,
                'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'noted_at' => $data['noted_at'],
                'note' => $data['note'],
                'measurements' => $data['measurements'] ?? null,
                'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
                'source' => $data['source'] ?? ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
                'authored_by_user_id' => Auth::id(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'clinical.progress_note.created',
                'Progress note created',
                subject: $note,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'progress_note_uuid' => $note->uuid,
                ],
            );

            $this->auditLogger->log('clinical.progress_note.created', $note, [
                'patient_uuid' => $patient->uuid,
                'progress_note_uuid' => $note->uuid,
            ]);

            return $note;
        });
    }
}
