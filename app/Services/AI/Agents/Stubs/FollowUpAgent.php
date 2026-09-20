<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;
use App\Services\AI\DTO\AgentContext;
use App\Services\AI\DTO\PromptRequest;

class FollowUpAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'follow_up';
    }

    public function name(): string
    {
        return 'Follow-up Assistant';
    }

    public function description(): string
    {
        return 'Drafts assistive follow-up outreach notes for clinic staff after discharge or no-show. Staff must review before sending.';
    }

    public function defaultFeature(): string
    {
        return 'clinic.follow_up';
    }

    public function buildRequest(AgentContext $context, string $systemPrompt, ?string $modelHint = null): PromptRequest
    {
        $enriched = $systemPrompt."\nOutput a short staff-facing draft only. Label it as AI-assisted draft requiring clinician review.";

        return parent::buildRequest($context, $enriched, $modelHint);
    }
}
