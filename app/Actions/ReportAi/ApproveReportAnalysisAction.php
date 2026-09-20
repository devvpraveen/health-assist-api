<?php

namespace App\Actions\ReportAi;

use App\Models\ReportAnalysis;
use App\Models\ReportAnalysisVersion;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\AI\Learning\LearningEngine;
use App\Services\Patients\PatientTimelineRecorder;
use App\Services\ReportAi\ReportAnalysisService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveReportAnalysisAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private ReportAnalysisService $reportAnalysisService,
        private LearningEngine $learningEngine,
    ) {}

    /**
     * @param  array{clinician_notes?: string|null, patient_explanation?: string|null}  $data
     */
    public function handle(ReportAnalysis $analysis, array $data, User $reviewer): ReportAnalysis
    {
        if ($analysis->status !== ReportAnalysis::STATUS_AWAITING_REVIEW) {
            throw ValidationException::withMessages([
                'status' => ['Only analyses awaiting review can be approved.'],
            ]);
        }

        return DB::transaction(function () use ($analysis, $data, $reviewer): ReportAnalysis {
            $locked = ReportAnalysis::query()->lockForUpdate()->findOrFail($analysis->id);

            if ($locked->status !== ReportAnalysis::STATUS_AWAITING_REVIEW) {
                throw ValidationException::withMessages([
                    'status' => ['Only analyses awaiting review can be approved.'],
                ]);
            }

            $explanation = array_key_exists('patient_explanation', $data)
                ? $data['patient_explanation']
                : $locked->patient_explanation;

            $notes = $data['clinician_notes'] ?? $locked->clinician_notes;

            $locked->update([
                'status' => ReportAnalysis::STATUS_APPROVED,
                'patient_explanation' => $explanation,
                'clinician_notes' => $notes,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $this->reportAnalysisService->storeVersion(
                $locked,
                ReportAnalysisVersion::KIND_FINAL,
                [
                    'status' => ReportAnalysis::STATUS_APPROVED,
                    'extracted_facts' => $locked->extracted_facts,
                    'interpretation' => $locked->interpretation,
                    'reference_range_findings' => $locked->reference_range_findings,
                    'safety_level' => $locked->safety_level,
                    'clinician_notes' => $notes,
                ],
                $explanation,
                ReportAnalysisVersion::SOURCE_CLINICIAN,
                $reviewer->id,
            );

            $patient = $locked->patient()->firstOrFail();
            $this->timelineRecorder->record(
                $patient,
                'report_analysis.approved',
                'Report analysis approved',
                subject: $locked,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'analysis_uuid' => $locked->uuid,
                    'reviewed_by' => $reviewer->id,
                ],
                actor: $reviewer,
            );

            $this->auditLogger->log('report_analysis.approved', $locked, [
                'patient_uuid' => $patient->uuid,
                'analysis_uuid' => $locked->uuid,
            ], $reviewer);

            $hadCorrection = array_key_exists('patient_explanation', $data)
                && filled($data['patient_explanation'])
                && $data['patient_explanation'] !== $analysis->patient_explanation;

            if ($hadCorrection) {
                $this->learningEngine->submitFeedback([
                    'tenant_id' => $locked->tenant_id,
                    'user_id' => $reviewer->id,
                    'patient_id' => $locked->patient_id,
                    'agent' => 'report',
                    'source' => 'doctor',
                    'original_output' => $analysis->patient_explanation,
                    'corrected_output' => $data['patient_explanation'],
                    'meta' => ['analysis_uuid' => $locked->uuid, 'kind' => 'patient_explanation'],
                ]);
            }

            $this->learningEngine->recordSignal(
                tenantId: (int) $locked->tenant_id,
                agent: 'report',
                signalType: 'clinician_approved',
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
