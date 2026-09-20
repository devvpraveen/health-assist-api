<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class ReviewAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'review';
    }

    public function name(): string
    {
        return 'Review Agent';
    }

    public function description(): string
    {
        return 'Assistive content and clinical-draft review helper. Flags uncertainty for human reviewers; never auto-approves medical content.';
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
        return 'review.assist';
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
        return ['flag_uncertainty', 'summarize_diff'];
    }
}
