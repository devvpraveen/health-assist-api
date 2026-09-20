<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;
use App\Services\AI\DTO\AgentContext;
use App\Services\AI\DTO\PromptRequest;

class PatientAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'patient';
    }

    public function name(): string
    {
        return 'Patient Assistant';
    }

    public function description(): string
    {
        return 'Assistive patient-facing agent for clarifying concerns, explaining next steps, and preparing clinician visits. Never diagnoses or prescribes.';
    }

    public function defaultFeature(): string
    {
        return 'patient.assist';
    }

    public function buildRequest(AgentContext $context, string $systemPrompt, ?string $modelHint = null): PromptRequest
    {
        $enriched = $systemPrompt;
        $clinicHint = $context->metadata['clinic_name'] ?? null;
        $locale = $context->metadata['locale'] ?? null;
        if (filled($clinicHint) || filled($locale)) {
            $enriched .= "\nContext: clinic=".($clinicHint ?: 'unknown').'; locale='.($locale ?: 'en').'.';
        }

        return parent::buildRequest($context, $enriched, $modelHint);
    }
}
