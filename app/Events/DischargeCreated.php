<?php

namespace App\Events;

use App\Models\ClinicalDischargeSummary;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DischargeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public ClinicalDischargeSummary $dischargeSummary) {}
}
