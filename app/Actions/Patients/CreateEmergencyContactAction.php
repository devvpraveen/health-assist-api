<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateEmergencyContactAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): PatientEmergencyContact
    {
        return DB::transaction(function () use ($patient, $data): PatientEmergencyContact {
            if (! empty($data['is_primary'])) {
                PatientEmergencyContact::query()
                    ->where('patient_id', $patient->id)
                    ->update(['is_primary' => false]);
            }

            $contact = PatientEmergencyContact::query()->create([
                ...$data,
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'is_primary' => (bool) ($data['is_primary'] ?? false),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'contact.added',
                'Emergency contact added',
                subject: $contact,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'contact_uuid' => $contact->uuid,
                ],
            );

            $this->auditLogger->log('emergency_contact.created', $contact, [
                'patient_uuid' => $patient->uuid,
                'contact_uuid' => $contact->uuid,
            ]);

            return $contact;
        });
    }
}
