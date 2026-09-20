<?php

namespace App\Actions\Patients;

use App\Contracts\RecordIntegrityVerifierInterface;
use App\Models\HealthRecord;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Integrity\IntegrityHasher;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateHealthRecordAction
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
    public function handle(Patient $patient, array $data): HealthRecord
    {
        return DB::transaction(function () use ($patient, $data): HealthRecord {
            $payload = [
                'category' => $data['category'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'recorded_at' => $data['recorded_at'] ?? null,
                'status' => $data['status'] ?? 'active',
            ];

            $hash = $this->integrityHasher->hash($payload);

            $record = HealthRecord::query()->create([
                ...$payload,
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'integrity_hash' => $hash,
            ]);

            $attest = $this->integrityVerifier->attest('health_record', (string) $record->id, $payload);

            $record->update([
                'integrity_status' => $attest['status'],
                'integrity_provider' => $attest['provider'],
                'integrity_proof_ref' => $attest['proof_ref'],
                'integrity_attested_at' => now(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'record.created',
                'Health record created',
                subject: $record,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'health_record_uuid' => $record->uuid,
                    'category' => $record->category,
                ],
            );

            $this->auditLogger->log('health_record.created', $record, [
                'patient_uuid' => $patient->uuid,
                'health_record_uuid' => $record->uuid,
                'category' => $record->category,
            ]);

            return $record->refresh();
        });
    }
}
