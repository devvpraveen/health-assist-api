<?php

namespace App\Services\Safety;

use App\Models\SafetyAssessment;
use App\Models\SafetyRule;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

/**
 * Deterministic red-flag / safety screening. Never calls an LLM.
 */
class SafetyEngine
{
    private const MIN_MEANINGFUL_CHARS = 8;

    /**
     * @param  array<string, mixed>|null  $structuredState
     * @param  array{conversation_id?: int|null, patient_id?: int|null, tenant_id?: int|null, model_hint?: string|null, persist?: bool}  $options
     */
    public function assess(string $text, ?array $structuredState = null, array $options = []): SafetyAssessmentResult
    {
        $normalized = mb_strtolower(trim($text));
        $persist = (bool) ($options['persist'] ?? false);
        $conversationId = $options['conversation_id'] ?? null;
        $patientId = $options['patient_id'] ?? null;
        $tenantId = $options['tenant_id'] ?? TenantContext::id();
        $modelHint = $options['model_hint'] ?? null;

        if ($this->isInsufficientInformation($normalized, $structuredState)) {
            $result = new SafetyAssessmentResult(
                level: SafetyAssessment::LEVEL_INSUFFICIENT_INFORMATION,
                action: SafetyRule::ACTION_CONTINUE,
                matchedRuleCodes: [],
                message: (string) config('health_guide.insufficient_information_message'),
                ruleVersionSnapshot: null,
                modelHint: $modelHint,
            );

            return $this->maybePersist($result, $normalized, $structuredState, $tenantId, $conversationId, $patientId, $persist);
        }

        /** @var Collection<int, SafetyRule> $rules */
        $rules = SafetyRule::query()
            ->active()
            ->get()
            ->sortBy(function (SafetyRule $rule): array {
                return [
                    SafetyRule::SEVERITY_PRIORITY[$rule->severity] ?? 99,
                    $rule->sort_order,
                    $rule->id,
                ];
            })
            ->values();

        $matched = [];
        $winner = null;

        foreach ($rules as $rule) {
            if (! $this->matches($normalized, $rule)) {
                continue;
            }

            $matched[] = $rule;

            if ($winner === null) {
                $winner = $rule;
            }
        }

        if ($winner === null) {
            $level = $this->levelFromStructuredState($structuredState) ?? SafetyAssessment::LEVEL_ROUTINE;
            $action = match ($level) {
                SafetyAssessment::LEVEL_CLINICIAN_REVIEW => SafetyRule::ACTION_REQUIRE_CLINICIAN_REVIEW,
                default => SafetyRule::ACTION_CONTINUE,
            };

            $result = new SafetyAssessmentResult(
                level: $level,
                action: $action,
                matchedRuleCodes: [],
                message: '',
                ruleVersionSnapshot: null,
                modelHint: $modelHint,
            );

            return $this->maybePersist($result, $normalized, $structuredState, $tenantId, $conversationId, $patientId, $persist);
        }

        $snapshot = collect($matched)->mapWithKeys(
            fn (SafetyRule $rule): array => [$rule->code => $rule->version]
        )->all();

        $message = $winner->message_template;
        if ($winner->severity === SafetyRule::SEVERITY_EMERGENCY) {
            $message = (string) config('health_guide.escalation.emergency', $message);
        } elseif ($winner->severity === SafetyRule::SEVERITY_URGENT) {
            $message = (string) config('health_guide.escalation.urgent', $message);
        }

        $result = new SafetyAssessmentResult(
            level: $winner->severity,
            action: $winner->action,
            matchedRuleCodes: array_values(array_map(fn (SafetyRule $r): string => $r->code, $matched)),
            message: $message,
            ruleVersionSnapshot: $snapshot,
            modelHint: $modelHint,
        );

        return $this->maybePersist($result, $normalized, $structuredState, $tenantId, $conversationId, $patientId, $persist);
    }

    private function matches(string $normalizedText, SafetyRule $rule): bool
    {
        if ($rule->pattern_type === SafetyRule::PATTERN_REGEX) {
            return @preg_match('/'.$rule->pattern.'/iu', $normalizedText) === 1;
        }

        $keywords = array_filter(array_map('trim', explode(',', mb_strtolower($rule->pattern))));

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($normalizedText, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $structuredState
     */
    private function isInsufficientInformation(string $normalizedText, ?array $structuredState): bool
    {
        if (mb_strlen($normalizedText) < self::MIN_MEANINGFUL_CHARS) {
            return true;
        }

        $tokens = preg_split('/\s+/u', $normalizedText, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) < 2 && empty($structuredState['complaint'] ?? null)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $structuredState
     */
    private function levelFromStructuredState(?array $structuredState): ?string
    {
        $urgency = $structuredState['urgency'] ?? null;

        return match ($urgency) {
            'emergency' => SafetyAssessment::LEVEL_EMERGENCY,
            'urgent' => SafetyAssessment::LEVEL_URGENT,
            'requires_clinician_review' => SafetyAssessment::LEVEL_CLINICIAN_REVIEW,
            'routine' => SafetyAssessment::LEVEL_ROUTINE,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $structuredState
     */
    private function maybePersist(
        SafetyAssessmentResult $result,
        string $normalizedText,
        ?array $structuredState,
        ?int $tenantId,
        ?int $conversationId,
        ?int $patientId,
        bool $persist,
    ): SafetyAssessmentResult {
        if (! $persist || $tenantId === null) {
            return $result;
        }

        $limit = (int) config('ai.audit.store_redacted_preview_chars', 200);
        $redacted = mb_strlen($normalizedText) <= $limit
            ? $normalizedText
            : mb_substr($normalizedText, 0, $limit).'…';

        $assessment = SafetyAssessment::query()->create([
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
            'patient_id' => $patientId,
            'input_category' => $structuredState['care_category'] ?? $structuredState['complaint'] ?? null,
            'input_redacted' => $redacted,
            'level' => $result->level,
            'matched_rule_codes' => $result->matchedRuleCodes,
            'action' => $result->action,
            'rule_version_snapshot' => $result->ruleVersionSnapshot,
            'model_hint' => $result->modelHint,
        ]);

        return new SafetyAssessmentResult(
            level: $result->level,
            action: $result->action,
            matchedRuleCodes: $result->matchedRuleCodes,
            message: $result->message,
            ruleVersionSnapshot: $result->ruleVersionSnapshot,
            assessmentId: $assessment->id,
            modelHint: $result->modelHint,
        );
    }
}
