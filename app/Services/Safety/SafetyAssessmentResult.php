<?php

namespace App\Services\Safety;

/**
 * Deterministic safety assessment result. Never produced by an LLM.
 */
readonly class SafetyAssessmentResult
{
    /**
     * @param  list<string>  $matchedRuleCodes
     * @param  array<string, mixed>|null  $ruleVersionSnapshot
     */
    public function __construct(
        public string $level,
        public string $action,
        public array $matchedRuleCodes = [],
        public string $message = '',
        public ?array $ruleVersionSnapshot = null,
        public ?int $assessmentId = null,
        public ?string $modelHint = null,
    ) {}

    public function isEscalation(): bool
    {
        return in_array($this->level, ['emergency', 'urgent'], true);
    }

    public function allowsProviderRecommendations(): bool
    {
        return ! $this->isEscalation();
    }

    public function allowsBooking(): bool
    {
        return $this->level !== 'emergency';
    }

    /**
     * @return array{level: string, action: string, matched_rules: list<string>, message: string}
     */
    public function toPayload(): array
    {
        return [
            'level' => $this->level,
            'action' => $this->action,
            'matched_rules' => $this->matchedRuleCodes,
            'message' => $this->message,
        ];
    }
}
