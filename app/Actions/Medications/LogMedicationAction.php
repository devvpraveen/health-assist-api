<?php

namespace App\Actions\Medications;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LogMedicationAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Medication $medication, array $data): MedicationLog
    {
        return DB::transaction(function () use ($medication, $data): MedicationLog {
            $scheduledFor = $data['scheduled_for'] ?? null;
            $status = $data['status'];
            $idempotencyKey = $data['idempotency_key'] ?? null;

            if (filled($idempotencyKey)) {
                $existing = MedicationLog::query()
                    ->where('medication_id', $medication->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            if ($scheduledFor !== null) {
                $duplicate = MedicationLog::query()
                    ->where('medication_id', $medication->id)
                    ->where('scheduled_for', $scheduledFor)
                    ->where('status', $status)
                    ->first();

                if ($duplicate !== null) {
                    return $duplicate;
                }
            }

            if ($status === MedicationLog::STATUS_MISSED && $scheduledFor !== null) {
                $taken = MedicationLog::query()
                    ->where('medication_id', $medication->id)
                    ->where('scheduled_for', $scheduledFor)
                    ->where('status', MedicationLog::STATUS_TAKEN)
                    ->exists();

                if ($taken) {
                    throw ValidationException::withMessages([
                        'status' => ['Cannot mark missed when a taken log already exists for this dose.'],
                    ]);
                }
            }

            $log = MedicationLog::query()->create([
                'tenant_id' => TenantContext::id() ?? $medication->tenant_id,
                'medication_id' => $medication->id,
                'patient_id' => $medication->patient_id,
                'schedule_id' => $data['schedule_id'] ?? null,
                'scheduled_for' => $scheduledFor,
                'logged_at' => $data['logged_at'] ?? now(),
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'logged_by_user_id' => Auth::id(),
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->timelineRecorder->record(
                $medication->patient,
                'medication.log.'.$status,
                'Medication dose '.$status,
                subject: $log,
                meta: [
                    'patient_uuid' => $medication->patient->uuid,
                    'medication_uuid' => $medication->uuid,
                    'log_uuid' => $log->uuid,
                    'status' => $status,
                ],
            );

            $this->auditLogger->log('medication.log.created', $log, [
                'medication_uuid' => $medication->uuid,
                'log_uuid' => $log->uuid,
                'status' => $status,
            ]);

            return $log;
        });
    }
}
