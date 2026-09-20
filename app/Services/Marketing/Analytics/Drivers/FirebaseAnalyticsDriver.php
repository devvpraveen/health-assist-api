<?php

namespace App\Services\Marketing\Analytics\Drivers;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Services\Marketing\Analytics\AnalyticsEvent;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Analytics is primarily client-side; server stub logs only.
 */
class FirebaseAnalyticsDriver implements AnalyticsGatewayInterface
{
    public function track(AnalyticsEvent $event): void
    {
        $appId = trim((string) config('marketing.firebase.app_id'));

        Log::info('marketing.firebase.stub', [
            'app_id' => $appId !== '' ? $appId : null,
            'name' => $event->name,
            'anonymous_id' => $event->anonymousId,
            'user_key' => $event->userKey,
            'properties' => $event->properties,
            'timestamp' => $event->timestamp->toIso8601String(),
        ]);
    }
}
