<?php

namespace App\Actions\Billing;

use App\Models\Patient;
use App\Models\PatientPackage;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;

class ConsumePatientPackageSessionAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    public function handle(Patient $patient, PatientPackage $patientPackage, int $sessions = 1): PatientPackage
    {
        return DB::transaction(function () use ($patient, $patientPackage, $sessions): PatientPackage {
            /** @var PatientPackage $locked */
            $locked = PatientPackage::query()->whereKey($patientPackage->id)->lockForUpdate()->firstOrFail();

            $updated = $locked->consumeSession($sessions);

            $this->timelineRecorder->record(
                $patient,
                'billing.package.session_consumed',
                'Package session consumed',
                subject: $updated,
                meta: [
                    'patient_package_uuid' => $updated->uuid,
                    'sessions_consumed' => $sessions,
                    'sessions_remaining' => $updated->sessions_remaining,
                ],
            );

            $this->auditLogger->log('billing.patient_package.session_consumed', $updated, [
                'patient_uuid' => $patient->uuid,
                'sessions_consumed' => $sessions,
                'sessions_remaining' => $updated->sessions_remaining,
            ]);

            return $updated->load('package');
        });
    }
}
