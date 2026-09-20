<?php

namespace App\Events\Marketing;

use App\Models\MarketingLead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketingLeadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public MarketingLead $lead) {}
}
