<?php

namespace App\Services\ReportAi;

use App\Contracts\Ocr\OcrProviderInterface;
use App\Models\ReportAnalysis;
use App\Models\ReportAnalysisVersion;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Services\Safety\SafetyEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ReportAnalysisService
{
    public function __construct(
        private readonly OcrProviderInterface $ocr,
        private readonly ReportExtractionService $extraction,
        private readonly AIOrchestrator $orchestrator,
        private readonly SafetyEngine $safetyEngine,
        private readonly AuditLogger $auditLogger,
        private readonly PatientTimelineRecorder $timelineRecorder,
    ) {}

    public function process(ReportAnalysis $analysis): ReportAnalysis
    {
        $analysis->refresh();

        if ($analysis->status === ReportAnalysis::STATUS_APPROVED
            || $analysis->status === ReportAnalysis::STATUS_REJECTED) {
            return $analysis;
        }

        $analysis->update(['status' => ReportAnalysis::STATUS_PROCESSING, 'error_message' => null]);

        try {
            return DB::transaction(function () use ($analysis): ReportAnalysis {
                $document = $analysis->document()->firstOrFail();
                $absolutePath = Storage::disk($document->disk)->path($document->path);

                if (! is_file($absolutePath)) {
                    throw new \RuntimeException('Document file not found on disk.');
                }

                $ocrResult = $this->ocr->extractText(
                    $absolutePath,
                    $document->mime_type,
                    $document->original_filename,
                );
                $this->storeVersion($analysis, ReportAnalysisVersion::KIND_OCR, [
                    'provider' => $ocrResult->provider,
                    'confidence' => $ocrResult->confidence,
                    'pages' => $ocrResult->pages,
                ], $ocrResult->text, ReportAnalysisVersion::SOURCE_SYSTEM);

                $extracted = $this->extraction->extract($ocrResult->text);
                $this->storeVersion($analysis, ReportAnalysisVersion::KIND_EXTRACTION, $extracted, null, ReportAnalysisVersion::SOURCE_SYSTEM);

                $factsJson = json_encode($extracted['extracted_facts'], JSON_THROW_ON_ERROR);
                $findingsJson = json_encode($extracted['reference_range_findings'], JSON_THROW_ON_ERROR);

                $interpretationResult = $this->orchestrator->run('report', new AgentContext(
                    tenantId: $analysis->tenant_id,
                    userId: null,
                    feature: 'report.interpretation',
                    input: $factsJson,
                    messages: [
                        [
                            'role' => 'user',
                            'content' => "Interpret these structured lab facts (not a diagnosis).\nFacts: {$factsJson}\nReference findings: {$findingsJson}",
                        ],
                    ],
                    metadata: [
                        'report_analysis_uuid' => $analysis->uuid,
                        'document_uuid' => $document->uuid,
                    ],
                    taskType: 'medical_document',
                ));

                $parsed = $this->parseAiJson($interpretationResult->content);
                $interpretation = [
                    'possible_interpretation' => (string) ($parsed['possible_interpretation'] ?? 'Assistive interpretation unavailable.'),
                    'uncertainty' => (string) ($parsed['uncertainty'] ?? 'AI interpretation is uncertain and requires clinician review.'),
                    'items_requiring_review' => array_values((array) ($parsed['items_requiring_review'] ?? ['Clinician review required before patient release.'])),
                    'requires_clinician_review' => true,
                ];

                if (isset($parsed['extracted_facts']) && is_array($parsed['extracted_facts'])) {
                    // Reinforcement only — never replace heuristic extracted_facts as source of truth.
                    $interpretation['ai_extracted_facts_reinforcement'] = $parsed['extracted_facts'];
                }

                $this->storeVersion(
                    $analysis,
                    ReportAnalysisVersion::KIND_INTERPRETATION,
                    $interpretation,
                    null,
                    ReportAnalysisVersion::SOURCE_AI,
                );

                $patientExplanation = (string) ($parsed['patient_explanation'] ?? '');
                if ($patientExplanation === '') {
                    $explanationResult = $this->orchestrator->run('report', new AgentContext(
                        tenantId: $analysis->tenant_id,
                        userId: null,
                        feature: 'report.patient_explanation',
                        input: $factsJson,
                        messages: [
                            [
                                'role' => 'user',
                                'content' => "Write a short patient-friendly explanation (assistive only).\nFacts: {$factsJson}\nInterpretation: ".json_encode($interpretation, JSON_THROW_ON_ERROR),
                            ],
                        ],
                        metadata: [
                            'report_analysis_uuid' => $analysis->uuid,
                        ],
                        taskType: 'medical_document',
                    ));
                    $explanationParsed = $this->parseAiJson($explanationResult->content);
                    $patientExplanation = (string) ($explanationParsed['patient_explanation']
                        ?? $explanationParsed['reply']
                        ?? 'Your clinician will review these results with you.');
                }

                $this->storeVersion(
                    $analysis,
                    ReportAnalysisVersion::KIND_PATIENT_EXPLANATION,
                    ['disclaimer' => config('report_ai.patient_explanation_disclaimer')],
                    $patientExplanation,
                    ReportAnalysisVersion::SOURCE_AI,
                );

                $safetyText = implode("\n", [
                    $ocrResult->text,
                    $factsJson,
                    $interpretation['possible_interpretation'],
                    $interpretation['uncertainty'],
                    implode('; ', $interpretation['items_requiring_review']),
                    $patientExplanation,
                ]);

                $safety = $this->safetyEngine->assess($safetyText, [
                    'care_category' => 'report_analysis',
                    'complaint' => 'lab_report',
                ], [
                    'persist' => true,
                    'tenant_id' => $analysis->tenant_id,
                    'patient_id' => $analysis->patient_id,
                    'model_hint' => 'report',
                ]);

                $analysis->update([
                    'status' => ReportAnalysis::STATUS_AWAITING_REVIEW,
                    'report_type' => $analysis->report_type ?? 'lab',
                    'ocr_provider' => $ocrResult->provider,
                    'ocr_raw_text' => $ocrResult->text,
                    'extracted_facts' => $extracted['extracted_facts'],
                    'interpretation' => $interpretation,
                    'reference_range_findings' => $extracted['reference_range_findings'],
                    'safety_level' => $safety->level,
                    'safety_assessment_id' => $safety->assessmentId,
                    'patient_explanation' => $patientExplanation,
                    'ai_usage_record_id' => $interpretationResult->usageRecordId,
                    'error_message' => null,
                ]);

                $patient = $analysis->patient()->firstOrFail();
                $this->timelineRecorder->record(
                    $patient,
                    'report_analysis.awaiting_review',
                    'Report analysis awaiting clinician review',
                    subject: $analysis,
                    meta: [
                        'patient_uuid' => $patient->uuid,
                        'analysis_uuid' => $analysis->uuid,
                        'document_uuid' => $document->uuid,
                        'safety_level' => $safety->level,
                    ],
                );

                $this->auditLogger->log('report_analysis.processed', $analysis, [
                    'patient_uuid' => $patient->uuid,
                    'analysis_uuid' => $analysis->uuid,
                    'document_uuid' => $document->uuid,
                    'status' => ReportAnalysis::STATUS_AWAITING_REVIEW,
                    'safety_level' => $safety->level,
                    'ocr_provider' => $ocrResult->provider,
                ]);

                return $analysis->fresh(['versions', 'document']);
            });
        } catch (Throwable $e) {
            $analysis->update([
                'status' => ReportAnalysis::STATUS_FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
            ]);

            $this->auditLogger->log('report_analysis.failed', $analysis, [
                'analysis_uuid' => $analysis->uuid,
                'error' => mb_substr($e->getMessage(), 0, 200),
            ]);

            throw $e;
        }
    }

    public function regeneratePatientExplanation(ReportAnalysis $analysis): ReportAnalysis
    {
        if ($analysis->status !== ReportAnalysis::STATUS_AWAITING_REVIEW) {
            throw new \InvalidArgumentException('Patient explanation can only be regenerated while awaiting review.');
        }

        $factsJson = json_encode($analysis->extracted_facts ?? [], JSON_THROW_ON_ERROR);
        $interpretationJson = json_encode($analysis->interpretation ?? [], JSON_THROW_ON_ERROR);

        $result = $this->orchestrator->run('report', new AgentContext(
            tenantId: $analysis->tenant_id,
            userId: null,
            feature: 'report.patient_explanation',
            input: $factsJson,
            messages: [
                [
                    'role' => 'user',
                    'content' => "Rewrite a short patient-friendly explanation (assistive only).\nFacts: {$factsJson}\nInterpretation: {$interpretationJson}",
                ],
            ],
            taskType: 'medical_document',
        ));

        $parsed = $this->parseAiJson($result->content);
        $explanation = (string) ($parsed['patient_explanation']
            ?? $parsed['reply']
            ?? 'Your clinician will review these results with you.');

        $this->storeVersion(
            $analysis,
            ReportAnalysisVersion::KIND_PATIENT_EXPLANATION,
            ['regenerated' => true, 'disclaimer' => config('report_ai.patient_explanation_disclaimer')],
            $explanation,
            ReportAnalysisVersion::SOURCE_AI,
        );

        $analysis->update([
            'patient_explanation' => $explanation,
            'ai_usage_record_id' => $result->usageRecordId ?? $analysis->ai_usage_record_id,
        ]);

        $this->auditLogger->log('report_analysis.explanation_regenerated', $analysis, [
            'analysis_uuid' => $analysis->uuid,
        ]);

        return $analysis->fresh(['versions']);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function storeVersion(
        ReportAnalysis $analysis,
        string $kind,
        ?array $payload = null,
        ?string $payloadText = null,
        string $source = ReportAnalysisVersion::SOURCE_SYSTEM,
        ?int $userId = null,
    ): ReportAnalysisVersion {
        $next = (int) $analysis->versions()->where('kind', $kind)->max('version') + 1;

        return $analysis->versions()->create([
            'version' => max(1, $next),
            'kind' => $kind,
            'payload' => $payload,
            'payload_text' => $payloadText,
            'created_by_user_id' => $userId,
            'source' => $source,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseAiJson(string $content): array
    {
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
