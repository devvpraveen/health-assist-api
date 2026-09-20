<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;

class UpdatePatientAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data): Patient {
            $patient->update($data);

            $this->timelineRecorder->record(
                $patient,
                'profile.updated',
                'Patient profile updated',
                subject: $patient,
                meta: ['patient_uuid' => $patient->uuid],
            );

            $this->auditLogger->log('patient.updated', $patient, [
                'patient_uuid' => $patient->uuid,
                'fields' => array_keys($data),
            ]);

            return $patient->refresh();
        });
    }
}
