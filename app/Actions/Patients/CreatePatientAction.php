<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreatePatientAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Patient
    {
        return DB::transaction(function () use ($data): Patient {
            $patient = Patient::query()->create([
                ...$data,
                'tenant_id' => TenantContext::id(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'patient.created',
                'Patient created',
                subject: $patient,
                meta: ['patient_uuid' => $patient->uuid],
            );

            $this->auditLogger->log('patient.created', $patient, [
                'patient_uuid' => $patient->uuid,
            ]);

            return $patient;
        });
    }
}
