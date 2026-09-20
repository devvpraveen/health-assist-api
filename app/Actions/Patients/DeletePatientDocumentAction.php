<?php

namespace App\Actions\Patients;

use App\Models\PatientDocument;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeletePatientDocumentAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    public function handle(PatientDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            $patient = $document->patient;
            $documentUuid = $document->uuid;
            $disk = $document->disk;
            $path = $document->path;

            $this->timelineRecorder->record(
                $patient,
                'document.deleted',
                'Document deleted',
                subject: $document,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'document_uuid' => $documentUuid,
                ],
            );

            $this->auditLogger->log('document.deleted', $document, [
                'patient_uuid' => $patient->uuid,
                'document_uuid' => $documentUuid,
            ]);

            $document->delete();

            Storage::disk($disk)->delete($path);
        });
    }
}
