<?php

namespace App\Actions\ReportAi;

use App\Exceptions\Modules\EntitlementLimitExceededException;
use App\Jobs\ProcessReportAnalysisJob;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\ReportAnalysis;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Modules\EntitlementUsageService;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartReportAnalysisAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private EntitlementUsageService $entitlementUsage,
    ) {}

    /**
     * @param  array{report_type?: string|null}  $data
     */
    public function handle(Patient $patient, PatientDocument $document, array $data = [], ?User $actor = null): ReportAnalysis
    {
        if ($document->patient_id !== $patient->id) {
            throw ValidationException::withMessages([
                'document' => ['Document does not belong to this patient.'],
            ]);
        }

        $tenantId = (int) (TenantContext::id() ?? $patient->tenant_id);
        $this->entitlementUsage->assertWithinLimit($tenantId, EntitlementUsageService::KEY_AI_REPORTS);

        return DB::transaction(function () use ($patient, $document, $data, $actor, $tenantId): ReportAnalysis {
            $analysis = ReportAnalysis::query()->create([
                'tenant_id' => $tenantId,
                'patient_id' => $patient->id,
                'document_id' => $document->id,
                'health_record_id' => $document->health_record_id,
                'status' => ReportAnalysis::STATUS_QUEUED,
                'report_type' => $data['report_type'] ?? $this->guessReportType($document),
            ]);

            $this->entitlementUsage->increment($tenantId, EntitlementUsageService::KEY_AI_REPORTS);

            $this->timelineRecorder->record(
                $patient,
                'report_analysis.queued',
                'Report analysis queued',
                subject: $analysis,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'analysis_uuid' => $analysis->uuid,
                    'document_uuid' => $document->uuid,
                ],
                actor: $actor,
            );

            $this->auditLogger->log('report_analysis.queued', $analysis, [
                'patient_uuid' => $patient->uuid,
                'analysis_uuid' => $analysis->uuid,
                'document_uuid' => $document->uuid,
            ], $actor);

            ProcessReportAnalysisJob::dispatch($analysis->id);

            return $analysis->fresh();
        });
    }

    private function guessReportType(PatientDocument $document): string
    {
        $category = mb_strtolower((string) $document->category);

        return match (true) {
            str_contains($category, 'lab') || str_contains($category, 'laboratory') => 'lab',
            str_contains($category, 'imag') || str_contains($category, 'xray') || str_contains($category, 'mri') => 'imaging',
            str_contains($category, 'prescription') || str_contains($category, 'rx') => 'prescription',
            default => 'other',
        };
    }
}
