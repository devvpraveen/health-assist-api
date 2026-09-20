<?php

namespace App\Services\Marketing\Analytics\Drivers;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Services\Marketing\Analytics\AnalyticsEvent;

class NullAnalyticsDriver implements AnalyticsGatewayInterface
{
    public function track(AnalyticsEvent $event): void
    {
        // no-op
    }
}
