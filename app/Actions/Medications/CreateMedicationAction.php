<?php

namespace App\Actions\Medications;

use App\Models\Medication;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateMedicationAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): Medication
    {
        return DB::transaction(function () use ($patient, $data): Medication {
            $medication = Medication::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'provider_id' => $data['provider_id'] ?? null,
                'name' => $data['name'],
                'dosage' => $data['dosage'],
                'frequency_label' => $data['frequency_label'] ?? null,
                'route' => $data['route'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? Medication::STATUS_ACTIVE,
                'created_by_user_id' => Auth::id(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'medication.created',
                'Medication added',
                subject: $medication,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'medication_uuid' => $medication->uuid,
                ],
            );

            $this->auditLogger->log('medication.created', $medication, [
                'patient_uuid' => $patient->uuid,
                'medication_uuid' => $medication->uuid,
            ]);

            return $medication;
        });
    }
}
