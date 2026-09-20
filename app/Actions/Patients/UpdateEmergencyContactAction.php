<?php

namespace App\Actions\Patients;

use App\Models\PatientEmergencyContact;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;

class UpdateEmergencyContactAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(PatientEmergencyContact $contact, array $data): PatientEmergencyContact
    {
        return DB::transaction(function () use ($contact, $data): PatientEmergencyContact {
            if (! empty($data['is_primary'])) {
                PatientEmergencyContact::query()
                    ->where('patient_id', $contact->patient_id)
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }

            $contact->update($data);

            $patient = $contact->patient;

            $this->timelineRecorder->record(
                $patient,
                'contact.updated',
                'Emergency contact updated',
                subject: $contact,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'contact_uuid' => $contact->uuid,
                ],
            );

            $this->auditLogger->log('emergency_contact.updated', $contact, [
                'patient_uuid' => $patient->uuid,
                'contact_uuid' => $contact->uuid,
            ]);

            return $contact->refresh();
        });
    }
}
