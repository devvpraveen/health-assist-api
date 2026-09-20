<?php

namespace Tests\Feature;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Services\Marketing\Analytics\AnalyticsEvent;
use App\Services\Marketing\Analytics\AnalyticsManager;
use App\Services\Marketing\Analytics\Drivers\Ga4AnalyticsDriver;
use App\Services\Marketing\Analytics\Drivers\LogAnalyticsDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AnalyticsDriversFanoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_fans_out_to_log_and_ga4_with_http_fake(): void
    {
        config([
            'marketing.analytics.enabled' => true,
            'marketing.ga4.measurement_id' => 'G-TEST',
            'marketing.ga4.api_secret' => 'secret',
            'marketing.ga4.endpoint' => 'https://www.google-analytics.com/mp/collect',
        ]);

        Http::fake([
            'www.google-analytics.com/*' => Http::response(['ok' => true], 204),
        ]);

        Log::spy();

        $manager = new AnalyticsManager([
            new LogAnalyticsDriver,
            new Ga4AnalyticsDriver,
        ]);

        $this->app->instance(AnalyticsGatewayInterface::class, $manager);

        $manager->track(new AnalyticsEvent(
            name: 'page_view',
            anonymousId: 'anon-fanout',
            properties: ['path' => '/en', 'locale' => 'en'],
            tenantId: 1,
        ));

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message, $context) => $message === 'marketing.analytics'
                && ($context['name'] ?? null) === 'page_view'
                && ($context['anonymous_id'] ?? null) === 'anon-fanout')
            ->once();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'google-analytics.com')
                && $request['client_id'] === 'anon-fanout'
                && ($request['events'][0]['name'] ?? null) === 'page_view';
        });
    }
}
