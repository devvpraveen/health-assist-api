<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class AppointmentAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'appointment';
    }
}
