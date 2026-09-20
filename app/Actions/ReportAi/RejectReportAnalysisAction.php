<?php

namespace App\Actions\ReportAi;

use App\Models\ReportAnalysis;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AI\Learning\LearningEngine;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectReportAnalysisAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private LearningEngine $learningEngine,
    ) {}

    /**
     * @param  array{clinician_notes?: string|null}  $data
     */
    public function handle(ReportAnalysis $analysis, array $data, User $reviewer): ReportAnalysis
    {
        if ($analysis->status !== ReportAnalysis::STATUS_AWAITING_REVIEW) {
            throw ValidationException::withMessages([
                'status' => ['Only analyses awaiting review can be rejected.'],
            ]);
        }

        return DB::transaction(function () use ($analysis, $data, $reviewer): ReportAnalysis {
            $locked = ReportAnalysis::query()->lockForUpdate()->findOrFail($analysis->id);

            if ($locked->status !== ReportAnalysis::STATUS_AWAITING_REVIEW) {
                throw ValidationException::withMessages([
                    'status' => ['Only analyses awaiting review can be rejected.'],
                ]);
            }

            $locked->update([
                'status' => ReportAnalysis::STATUS_REJECTED,
                'clinician_notes' => $data['clinician_notes'] ?? $locked->clinician_notes,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $patient = $locked->patient()->firstOrFail();
            $this->timelineRecorder->record(
                $patient,
                'report_analysis.rejected',
                'Report analysis rejected',
                subject: $locked,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'analysis_uuid' => $locked->uuid,
                    'reviewed_by' => $reviewer->id,
                ],
                actor: $reviewer,
            );

            $this->auditLogger->log('report_analysis.rejected', $locked, [
                'patient_uuid' => $patient->uuid,
                'analysis_uuid' => $locked->uuid,
            ], $reviewer);

            $this->learningEngine->recordSignal(
                tenantId: (int) $locked->tenant_id,
                agent: 'report',
                signalType: 'clinician_rejected',
                source: 'doctor',
                actorUserId: $reviewer->id,
                patientId: $locked->patient_id,
                subject: $locked,
                payload: ['analysis_uuid' => $locked->uuid],
            );

            return $locked->fresh(['versions']);
        });
    }
}
