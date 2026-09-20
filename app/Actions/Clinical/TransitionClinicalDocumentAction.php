<?php

namespace App\Actions\Clinical;

use App\Events\DischargeCreated;
use App\Models\ClinicalDischargeSummary;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TransitionClinicalDocumentAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array{status: string}  $data
     */
    public function handle(Model $document, Patient $patient, array $data, string $auditPrefix, string $timelineEvent): Model
    {
        return DB::transaction(function () use ($document, $patient, $data, $auditPrefix, $timelineEvent): Model {
            $locked = $document->newQuery()->lockForUpdate()->findOrFail($document->getKey());
            $previous = (string) $locked->getAttribute('status');
            $payload = ClinicalDocumentWorkflow::applyTransition($locked, $data['status']);
            $locked->update($payload);

            $this->timelineRecorder->record(
                $patient,
                $timelineEvent,
                class_basename($locked).' status updated',
                subject: $locked,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'document_uuid' => $locked->getAttribute('uuid'),
                    'from' => $previous,
                    'to' => $data['status'],
                ],
            );

            $this->auditLogger->log("{$auditPrefix}.transitioned", $locked, [
                'patient_uuid' => $patient->uuid,
                'document_uuid' => $locked->getAttribute('uuid'),
                'from' => $previous,
                'to' => $data['status'],
            ]);

            if ($locked instanceof ClinicalDischargeSummary
                && $data['status'] === ClinicalDocumentWorkflow::STATUS_APPROVED) {
                event(new DischargeCreated($locked->fresh()));
            }

            return $locked->fresh();
        });
    }
}
