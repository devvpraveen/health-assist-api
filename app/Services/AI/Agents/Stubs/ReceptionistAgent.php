<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;
use App\Services\AI\DTO\AgentContext;
use App\Services\AI\DTO\PromptRequest;

class ReceptionistAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'receptionist';
    }

    public function name(): string
    {
        return 'Receptionist Assistant';
    }

    public function description(): string
    {
        return 'Clinic receptionist agent for WhatsApp/clinic intents, appointment triage summaries, and staff handoff notes. Never diagnoses or prescribes.';
    }

    public function defaultFeature(): string
    {
        return 'whatsapp.receptionist';
    }

    public function buildRequest(AgentContext $context, string $systemPrompt, ?string $modelHint = null): PromptRequest
    {
        $enriched = $systemPrompt;
        $bits = [];
        foreach (['clinic_name', 'branch_name', 'channel', 'handoff_reason'] as $key) {
            if (filled($context->metadata[$key] ?? null)) {
                $bits[] = $key.'='.$context->metadata[$key];
            }
        }
        if ($bits !== []) {
            $enriched .= "\nFront-desk context: ".implode('; ', $bits).'.';
        }

        return parent::buildRequest($context, $enriched, $modelHint);
    }
}
