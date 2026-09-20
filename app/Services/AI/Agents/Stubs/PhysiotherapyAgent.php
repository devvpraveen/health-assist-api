<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class PhysiotherapyAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'physiotherapy';
    }

    public function name(): string
    {
        return 'Physiotherapy Agent';
    }

    public function description(): string
    {
        return 'Assistive physiotherapy education and exercise-plan drafting for clinician review. Never diagnoses or replaces a physiotherapist.';
    }

    public function layer(): string
    {
        return 'clinical';
    }

    public function maturity(): string
    {
        return 'stub';
    }

    public function defaultFeature(): string
    {
        return 'physiotherapy.assist';
    }

    public function defaultTaskType(): string
    {
        return 'clinical_documentation';
    }

    /**
     * @return list<string>
     */
    public function tools(): array
    {
        return ['draft_exercise_plan', 'explain_modality'];
    }
}
