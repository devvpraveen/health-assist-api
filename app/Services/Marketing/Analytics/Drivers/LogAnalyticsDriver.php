<?php

namespace App\Services\Marketing\Analytics\Drivers;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Services\Marketing\Analytics\AnalyticsEvent;
use Illuminate\Support\Facades\Log;

class LogAnalyticsDriver implements AnalyticsGatewayInterface
{
    public function track(AnalyticsEvent $event): void
    {
        Log::info('marketing.analytics', [
            'name' => $event->name,
            'tenant_id' => $event->tenantId,
            'anonymous_id' => $event->anonymousId,
            'user_key' => $event->userKey,
            'properties' => $event->properties,
            'timestamp' => $event->timestamp->toIso8601String(),
        ]);
    }
}
