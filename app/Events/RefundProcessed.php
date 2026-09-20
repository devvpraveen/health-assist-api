<?php

namespace App\Events;

use App\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Refund $refund) {}
}
