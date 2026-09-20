<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class VoiceAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'voice';
    }
}
