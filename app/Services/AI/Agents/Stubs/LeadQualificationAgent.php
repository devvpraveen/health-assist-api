<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class LeadQualificationAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'lead_qualification';
    }

    public function name(): string
    {
        return 'Lead Qualification Agent';
    }

    public function description(): string
    {
        return 'Assistive lead scoring and handoff notes for clinic growth workflows. Never diagnoses or makes clinical claims.';
    }

    public function layer(): string
    {
        return 'business';
    }

    public function maturity(): string
    {
        return 'stub';
    }

    public function defaultFeature(): string
    {
        return 'lead_qualification.assist';
    }

    public function defaultTaskType(): string
    {
        return 'classification';
    }

    /**
     * @return list<string>
     */
    public function tools(): array
    {
        return ['score_lead', 'draft_handoff'];
    }
}
