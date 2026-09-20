<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class ProviderRecommendationAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'provider_recommendation';
    }
}
