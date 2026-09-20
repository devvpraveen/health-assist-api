<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Models\PatientHealthProfile;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class UpsertHealthProfileAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): PatientHealthProfile
    {
        return DB::transaction(function () use ($patient, $data): PatientHealthProfile {
            $profile = PatientHealthProfile::query()->updateOrCreate(
                ['patient_id' => $patient->id],
                [
                    ...$data,
                    'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                ],
            );

            $this->timelineRecorder->record(
                $patient,
                'health_profile.updated',
                'Health profile updated',
                subject: $profile,
                meta: ['patient_uuid' => $patient->uuid],
            );

            $this->auditLogger->log('health_profile.updated', $profile, [
                'patient_uuid' => $patient->uuid,
            ]);

            return $profile;
        });
    }
}
