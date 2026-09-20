<?php

namespace App\Contracts\Marketing;

use App\Services\Marketing\Analytics\AnalyticsEvent;

interface AnalyticsGatewayInterface
{
    public function track(AnalyticsEvent $event): void;
}
