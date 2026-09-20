<?php

namespace App\Services\AI\Agents\Stubs;

use App\Services\AI\Agents\AbstractAgent;

class MedicationAgent extends AbstractAgent
{
    public function key(): string
    {
        return 'medication';
    }
}
