<?php

namespace App\Actions\HealthGuide;

use App\Models\HealthGuideConversation;
use App\Models\HealthGuideMessage;
use App\Models\SafetyAssessment;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Services\AI\Learning\MemoryService;
use App\Services\Appointments\AvailabilityService;
use App\Services\AuditLogger;
use App\Services\HealthGuide\HealthIntentExtractor;
use App\Services\Providers\ProviderRankingService;
use App\Services\Safety\SafetyAssessmentResult;
use App\Services\Safety\SafetyEngine;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SendHealthGuideMessageAction
{
    public function __construct(
        private SafetyEngine $safetyEngine,
        private HealthIntentExtractor $intentExtractor,
        private ProviderRankingService $rankingService,
        private AvailabilityService $availabilityService,
        private AIOrchestrator $orchestrator,
        private AuditLogger $auditLogger,
        private MemoryService $memoryService,
    ) {}

    /**
     * @return array{
     *     conversation: HealthGuideConversation,
     *     message: HealthGuideMessage,
     *     structured_state: array<string, mixed>,
     *     safety: array<string, mixed>,
     *     recommendations: list<array<string, mixed>>,
     *     disclaimer: string
     * }
     */
    public function handle(HealthGuideConversation $conversation, string $content): array
    {
        $tenantId = TenantContext::id() ?? $conversation->tenant_id;
        if ($tenantId === null) {
            throw ValidationException::withMessages([
                'tenant' => ['Tenant context is required.'],
            ]);
        }

        return DB::transaction(function () use ($conversation, $content, $tenantId): array {
            $userMessage = HealthGuideMessage::query()->create([
                'conversation_id' => $conversation->id,
                'tenant_id' => $tenantId,
                'role' => HealthGuideMessage::ROLE_USER,
                'content' => $content,
            ]);

            $safety = $this->safetyEngine->assess($content, $conversation->structured_state, [
                'persist' => true,
                'conversation_id' => $conversation->id,
                'patient_id' => $conversation->patient_id,
                'tenant_id' => $tenantId,
            ]);

            $conversation->safety_level = $safety->level;
            $conversation->last_message_at = now();

            if ($safety->isEscalation()) {
                $conversation->status = HealthGuideConversation::STATUS_ESCALATED;
                $conversation->save();

                $assistantMessage = HealthGuideMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'tenant_id' => $tenantId,
                    'role' => HealthGuideMessage::ROLE_ASSISTANT,
                    'content' => $safety->message !== ''
                        ? $safety->message
                        : (string) config('health_guide.escalation.'.$safety->level),
                    'meta' => [
                        'type' => 'safety_escalation',
                        'safety_level' => $safety->level,
                        'matched_rules' => $safety->matchedRuleCodes,
                        'assessment_id' => $safety->assessmentId,
                    ],
                ]);

                $this->auditLogger->log('health_guide.safety.escalated', $conversation, [
                    'conversation_uuid' => $conversation->uuid,
                    'level' => $safety->level,
                    'matched_rules' => $safety->matchedRuleCodes,
                ]);

                return $this->response(
                    $conversation->fresh(['messages', 'patient']),
                    $assistantMessage,
                    $conversation->structured_state ?? [],
                    $safety,
                    [],
                );
            }

            $structuredState = $this->intentExtractor->extract(
                $content,
                $conversation->structured_state,
            );

            // Safety always re-run conceptually already done on user text; sync urgency from safety.
            if ($safety->level !== SafetyAssessment::LEVEL_INSUFFICIENT_INFORMATION) {
                $structuredState['urgency'] = match ($safety->level) {
                    SafetyAssessment::LEVEL_CLINICIAN_REVIEW => 'requires_clinician_review',
                    default => $safety->level,
                };
            }

            $conversation->structured_state = $structuredState;
            $conversation->save();

            $this->memoryService->rememberConversation(
                tenantId: (int) $tenantId,
                conversationRef: $conversation->uuid,
                agent: 'health_guide',
                structured: array_filter([
                    'body_area' => $structuredState['body_area'] ?? $structuredState['complaint'] ?? null,
                    'symptom' => $structuredState['complaint'] ?? null,
                    'trigger' => $structuredState['trigger'] ?? null,
                    'duration' => $structuredState['duration'] ?? null,
                    'intent' => $structuredState['intent'] ?? null,
                    'care_category' => $structuredState['care_category'] ?? null,
                    'urgency' => $structuredState['urgency'] ?? null,
                ], fn ($v) => $v !== null && $v !== ''),
                patientId: $conversation->patient_id,
                userId: $conversation->user_id,
            );

            $recommendations = [];
            if (
                $safety->allowsProviderRecommendations()
                && $this->intentExtractor->hasEnoughInfoForRecommendations($structuredState)
                && ($structuredState['intent'] ?? null) === 'find_provider'
            ) {
                $recommendations = $this->buildRecommendations($tenantId, $structuredState);
            }

            try {
                $agentResult = $this->orchestrator->run('health_guide', new AgentContext(
                    tenantId: (int) $tenantId,
                    userId: $conversation->user_id,
                    feature: 'health_guide.conversation',
                    input: $content,
                    messages: [
                        [
                            'role' => 'user',
                            'content' => $this->buildNonPhiPrompt($content, $structuredState, $safety, $recommendations),
                        ],
                    ],
                    metadata: [
                        'conversation_uuid' => $conversation->uuid,
                        'conversation_ref' => $conversation->uuid,
                        'safety_level' => $safety->level,
                        'structured_state_summary' => [
                            'intent' => $structuredState['intent'] ?? null,
                            'complaint' => $structuredState['complaint'] ?? null,
                            'care_category' => $structuredState['care_category'] ?? null,
                            'urgency' => $structuredState['urgency'] ?? null,
                        ],
                    ],
                ));
                $assistantContent = $agentResult->content;
                $usageRecordId = $agentResult->usageRecordId;
                $auditLogId = $agentResult->auditLogId;
            } catch (\App\Exceptions\AI\AiQuotaExceededException $e) {
                $assistantContent = 'AI usage for this organization has reached its monthly limit. A clinician or staff member can still help you directly — please contact the clinic, or try again next month.';
                $usageRecordId = null;
                $auditLogId = null;
            } catch (\Throwable $e) {
                report($e);
                $assistantContent = 'I could not generate an AI reply just now. Your message was saved. Please try again shortly, or contact the clinic for help. This tool is assistive only and does not diagnose or prescribe.';
                $usageRecordId = null;
                $auditLogId = null;
            }

            if ($safety->level === SafetyAssessment::LEVEL_CLINICIAN_REVIEW && $safety->message !== '') {
                $assistantContent = $safety->message."\n\n".$assistantContent;
            }

            $assistantMessage = HealthGuideMessage::query()->create([
                'conversation_id' => $conversation->id,
                'tenant_id' => $tenantId,
                'role' => HealthGuideMessage::ROLE_ASSISTANT,
                'content' => $assistantContent,
                'ai_usage_record_id' => $usageRecordId,
                'meta' => [
                    'type' => 'health_guide_reply',
                    'safety_level' => $safety->level,
                    'recommendation_provider_ids' => array_column($recommendations, 'id'),
                    'usage_record_id' => $usageRecordId,
                    'audit_log_id' => $auditLogId,
                ],
            ]);

            $this->auditLogger->log('health_guide.message.sent', $conversation, [
                'conversation_uuid' => $conversation->uuid,
                'user_message_uuid' => $userMessage->uuid,
                'assistant_message_uuid' => $assistantMessage->uuid,
                'safety_level' => $safety->level,
            ]);

            return $this->response(
                $conversation->fresh(['messages', 'patient']),
                $assistantMessage,
                $structuredState,
                $safety,
                $recommendations,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $structuredState
     * @return list<array<string, mixed>>
     */
    private function buildRecommendations(int $tenantId, array $structuredState): array
    {
        $ranked = $this->rankingService->rank($tenantId, [
            'care_category' => $structuredState['care_category'] ?? null,
            'complaint' => $structuredState['complaint'] ?? null,
            'city' => $structuredState['city'] ?? null,
            'language' => $structuredState['locale_hint'] ?? null,
        ]);

        $slotsLimit = (int) config('health_guide.ranking.next_slots_limit', 3);
        $days = (int) config('health_guide.ranking.next_slots_days', 7);
        $from = CarbonImmutable::now()->startOfDay();

        return array_map(function (array $item) use ($slotsLimit, $days, $from): array {
            $nextSlots = [];
            if ($item['availability'] ?? false) {
                try {
                    $nextSlots = array_slice(
                        $this->availabilityService->slots(
                            (int) $item['id'],
                            $from,
                            $days,
                        ),
                        0,
                        $slotsLimit,
                    );
                } catch (\Throwable) {
                    $nextSlots = [];
                }
            }

            $item['next_slots'] = $nextSlots;
            $item['explanation'] = $this->explainRanking($item);

            return $item;
        }, $ranked);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function explainRanking(array $item): string
    {
        $parts = [];
        if ($item['specialty_match'] ?? false) {
            $parts[] = 'specialty aligns with your care category';
        }
        if ($item['condition_match'] ?? false) {
            $parts[] = 'profile suggests relevance to your complaint';
        }
        if ($item['availability'] ?? false) {
            $parts[] = 'has an active schedule';
        }
        if (($item['verification_status'] ?? null) === 'verified') {
            $parts[] = 'verified provider';
        }

        $name = $item['display_name'] ?? 'Provider';
        $score = $item['score'] ?? 0;

        return $parts === []
            ? "{$name} scored {$score} on deterministic ranking factors."
            : "{$name} ranked highly because ".implode(', ', $parts).'.';
    }

    /**
     * @param  array<string, mixed>  $structuredState
     * @param  list<array<string, mixed>>  $recommendations
     */
    private function buildNonPhiPrompt(
        string $userContent,
        array $structuredState,
        SafetyAssessmentResult $safety,
        array $recommendations,
    ): string {
        $providerLines = [];
        foreach ($recommendations as $rec) {
            $providerLines[] = sprintf(
                '%s (score %s; reasons: %s)',
                $rec['display_name'] ?? 'Provider',
                $rec['score'] ?? 0,
                implode(',', $rec['match_reason'] ?? []),
            );
        }

        return implode("\n", [
            'User message (assist only; do not diagnose or prescribe): '.$userContent,
            'Structured state summary: '.json_encode([
                'intent' => $structuredState['intent'] ?? null,
                'complaint' => $structuredState['complaint'] ?? null,
                'duration' => $structuredState['duration'] ?? null,
                'severity' => $structuredState['severity'] ?? null,
                'care_category' => $structuredState['care_category'] ?? null,
                'urgency' => $structuredState['urgency'] ?? null,
                'missing_fields' => $structuredState['missing_fields'] ?? [],
            ]),
            'Safety level (authoritative, do not override): '.$safety->level,
            'Ranked providers (do not re-sort): '.($providerLines === [] ? 'none' : implode('; ', $providerLines)),
        ]);
    }

    /**
     * @param  array<string, mixed>  $structuredState
     * @param  list<array<string, mixed>>  $recommendations
     * @return array{
     *     conversation: HealthGuideConversation,
     *     message: HealthGuideMessage,
     *     structured_state: array<string, mixed>,
     *     safety: array<string, mixed>,
     *     recommendations: list<array<string, mixed>>,
     *     disclaimer: string
     * }
     */
    private function response(
        HealthGuideConversation $conversation,
        HealthGuideMessage $message,
        array $structuredState,
        SafetyAssessmentResult $safety,
        array $recommendations,
    ): array {
        return [
            'conversation' => $conversation,
            'message' => $message,
            'structured_state' => $structuredState,
            'safety' => $safety->toPayload(),
            'recommendations' => $recommendations,
            'disclaimer' => (string) config('health_guide.disclaimer'),
        ];
    }
}
