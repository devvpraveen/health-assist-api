<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class WellnessAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'wellness';
    }
}
