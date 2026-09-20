<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class MarketingAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'marketing';
    }
}
