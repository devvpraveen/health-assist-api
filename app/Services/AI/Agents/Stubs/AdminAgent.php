<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class AdminAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'admin';
    }
}
