<?php

namespace App\Services\AI\Agents;

class HealthGuideAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'health_guide';
    }

    public function name(): string
    {
        return 'Health Guide';
    }

    public function description(): string
    {
        return 'Conversational Health Assist guide for intent, safety-aware guidance, and provider discovery explanations.';
    }

    public function defaultFeature(): string
    {
        return 'health_guide.conversation';
    }

    public function defaultTaskType(): string
    {
        return 'general_conversation';
    }
}
