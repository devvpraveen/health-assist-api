<?php

namespace App\Services\Marketing\Analytics\Drivers;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Services\Marketing\Analytics\AnalyticsEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mixpanel track stub. Network calls must be Http::fake()'d in tests/CI.
 */
class MixpanelAnalyticsDriver implements AnalyticsGatewayInterface
{
    public function track(AnalyticsEvent $event): void
    {
        $token = trim((string) config('marketing.mixpanel.token'));

        if ($token === '') {
            Log::debug('marketing.mixpanel.skipped', ['reason' => 'missing_token', 'name' => $event->name]);

            return;
        }

        $endpoint = (string) config('marketing.mixpanel.endpoint');

        $payload = [[
            'event' => $event->name,
            'properties' => array_merge($event->properties, [
                'token' => $token,
                'distinct_id' => $event->userKey ?? $event->anonymousId,
                'time' => $event->timestamp->getTimestamp(),
                'tenant_id' => $event->tenantId,
            ]),
        ]];

        Http::asJson()->post($endpoint, [
            'data' => base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ]);
    }
}
