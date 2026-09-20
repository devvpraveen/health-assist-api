<?php

namespace App\Services\Marketing\Analytics;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Services\Marketing\Analytics\Drivers\FirebaseAnalyticsDriver;
use App\Services\Marketing\Analytics\Drivers\Ga4AnalyticsDriver;
use App\Services\Marketing\Analytics\Drivers\LogAnalyticsDriver;
use App\Services\Marketing\Analytics\Drivers\MixpanelAnalyticsDriver;
use App\Services\Marketing\Analytics\Drivers\NullAnalyticsDriver;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class AnalyticsManager implements AnalyticsGatewayInterface
{
    /** @var list<AnalyticsGatewayInterface> */
    private array $drivers;

    /**
     * @param  list<AnalyticsGatewayInterface>|null  $drivers
     */
    public function __construct(?array $drivers = null)
    {
        $this->drivers = $drivers ?? $this->resolveConfiguredDrivers();
    }

    public function track(AnalyticsEvent $event): void
    {
        if (! config('marketing.analytics.enabled', true)) {
            return;
        }

        foreach ($this->drivers as $driver) {
            try {
                $driver->track($event);
            } catch (Throwable $e) {
                Log::warning('marketing.analytics.driver_failed', [
                    'driver' => $driver::class,
                    'message' => $e->getMessage(),
                    'event' => $event->name,
                ]);
            }
        }
    }

    /**
     * @return list<AnalyticsGatewayInterface>
     */
    private function resolveConfiguredDrivers(): array
    {
        $names = config('marketing.analytics.drivers', ['log']);

        if ($names === [] || $names === null) {
            $names = ['log'];
        }

        $resolved = [];

        foreach ($names as $name) {
            $resolved[] = match ((string) $name) {
                'log' => new LogAnalyticsDriver,
                'null' => new NullAnalyticsDriver,
                'ga4' => new Ga4AnalyticsDriver,
                'mixpanel' => new MixpanelAnalyticsDriver,
                'firebase' => new FirebaseAnalyticsDriver,
                default => throw new InvalidArgumentException("Unknown analytics driver [{$name}]."),
            };
        }

        return $resolved;
    }
}
