<?php

namespace App\Actions\Patients;

use App\Contracts\RecordIntegrityVerifierInterface;
use App\Models\HealthRecord;
use App\Services\AuditLogger;
use App\Services\Integrity\IntegrityHasher;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;

class UpdateHealthRecordAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private IntegrityHasher $integrityHasher,
        private RecordIntegrityVerifierInterface $integrityVerifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(HealthRecord $record, array $data): HealthRecord
    {
        return DB::transaction(function () use ($record, $data): HealthRecord {
            $record->fill($data);

            $payload = [
                'category' => $record->category,
                'title' => $record->title,
                'description' => $record->description,
                'recorded_at' => $record->recorded_at?->toIso8601String(),
                'status' => $record->status,
            ];

            $hash = $this->integrityHasher->hash($payload);
            $attest = $this->integrityVerifier->attest('health_record', (string) $record->id, $payload);

            $record->integrity_hash = $hash;
            $record->integrity_status = $attest['status'];
            $record->integrity_provider = $attest['provider'];
            $record->integrity_proof_ref = $attest['proof_ref'];
            $record->integrity_attested_at = now();
            $record->save();

            $patient = $record->patient;

            $this->timelineRecorder->record(
                $patient,
                'record.updated',
                'Health record updated',
                subject: $record,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'health_record_uuid' => $record->uuid,
                ],
            );

            $this->auditLogger->log('health_record.updated', $record, [
                'patient_uuid' => $patient->uuid,
                'health_record_uuid' => $record->uuid,
            ]);

            return $record->refresh();
        });
    }
}
