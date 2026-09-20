<?php

namespace App\Actions\Patients;

use App\Contracts\RecordIntegrityVerifierInterface;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Integrity\IntegrityHasher;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadPatientDocumentAction
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
    public function handle(Patient $patient, UploadedFile $file, array $data = [], ?User $uploader = null): PatientDocument
    {
        return DB::transaction(function () use ($patient, $file, $data, $uploader): PatientDocument {
            $tenantId = TenantContext::id() ?? $patient->tenant_id;
            $documentUuid = (string) Str::uuid();
            $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
            $relativePath = sprintf(
                'patient-documents/%d/%s/%s.%s',
                $tenantId,
                $patient->uuid,
                $documentUuid,
                $extension,
            );

            Storage::disk('local')->putFileAs(
                dirname($relativePath),
                $file,
                basename($relativePath),
            );

            $checksum = hash_file('sha256', $file->getRealPath() ?: $file->getPathname());

            $payload = [
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize() ?: 0,
                'checksum' => $checksum,
                'category' => $data['category'] ?? null,
            ];

            $hash = $this->integrityHasher->hash($payload);

            $document = PatientDocument::query()->create([
                'uuid' => $documentUuid,
                'tenant_id' => $tenantId,
                'patient_id' => $patient->id,
                'health_record_id' => $data['health_record_id'] ?? null,
                'category' => $data['category'] ?? null,
                'original_filename' => $payload['original_filename'],
                'mime_type' => $payload['mime_type'],
                'size' => $payload['size'],
                'disk' => 'local',
                'path' => $relativePath,
                'checksum' => $checksum,
                'visibility' => 'private',
                'uploaded_by' => $uploader?->id,
                'integrity_hash' => $hash,
            ]);

            $attest = $this->integrityVerifier->attest('patient_document', (string) $document->id, $payload);

            $document->update([
                'integrity_status' => $attest['status'],
                'integrity_provider' => $attest['provider'],
                'integrity_proof_ref' => $attest['proof_ref'],
                'integrity_attested_at' => now(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'document.uploaded',
                'Document uploaded',
                subject: $document,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'document_uuid' => $document->uuid,
                    'mime_type' => $document->mime_type,
                    'size' => $document->size,
                ],
            );

            $this->auditLogger->log('document.uploaded', $document, [
                'patient_uuid' => $patient->uuid,
                'document_uuid' => $document->uuid,
                'mime_type' => $document->mime_type,
                'size' => $document->size,
            ]);

            return $document->refresh();
        });
    }
}
