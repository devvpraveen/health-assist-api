<?php

namespace App\Actions\Medications;

use App\Models\Medication;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;

class UpdateMedicationAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Medication $medication, array $data): Medication
    {
        return DB::transaction(function () use ($medication, $data): Medication {
            $medication->update($data);

            $this->timelineRecorder->record(
                $medication->patient,
                'medication.updated',
                'Medication updated',
                subject: $medication,
                meta: [
                    'patient_uuid' => $medication->patient->uuid,
                    'medication_uuid' => $medication->uuid,
                ],
            );

            $this->auditLogger->log('medication.updated', $medication, [
                'patient_uuid' => $medication->patient->uuid,
                'medication_uuid' => $medication->uuid,
            ]);

            return $medication->refresh();
        });
    }
}
