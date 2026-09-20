<?php

namespace App\Services\Marketing\Analytics\Drivers;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Services\Marketing\Analytics\AnalyticsEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GA4 Measurement Protocol stub. Posts only allowlisted event payloads.
 * Network calls must be Http::fake()'d in tests/CI.
 */
class Ga4AnalyticsDriver implements AnalyticsGatewayInterface
{
    public function track(AnalyticsEvent $event): void
    {
        $measurementId = trim((string) config('marketing.ga4.measurement_id'));
        $apiSecret = trim((string) config('marketing.ga4.api_secret'));

        if ($measurementId === '' || $apiSecret === '') {
            Log::debug('marketing.ga4.skipped', ['reason' => 'missing_credentials', 'name' => $event->name]);

            return;
        }

        $endpoint = (string) config('marketing.ga4.endpoint');

        Http::asJson()->post($endpoint.'?'.http_build_query([
            'measurement_id' => $measurementId,
            'api_secret' => $apiSecret,
        ]), [
            'client_id' => $event->anonymousId,
            'user_id' => $event->userKey,
            'timestamp_micros' => $event->timestamp->getTimestampMs() * 1000,
            'events' => [[
                'name' => preg_replace('/[^a-zA-Z0-9_]/', '_', $event->name) ?: 'event',
                'params' => array_merge($event->properties, array_filter([
                    'tenant_id' => $event->tenantId,
                ])),
            ]],
        ]);
    }
}
