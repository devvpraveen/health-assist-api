<?php

namespace App\Services\Marketing\Analytics;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class AnalyticsEvent
{
    public readonly CarbonInterface $timestamp;

    /**
     * @param  array<string, scalar|null>  $properties
     */
    public function __construct(
        public readonly string $name,
        public readonly string $anonymousId,
        public readonly array $properties = [],
        public readonly ?int $tenantId = null,
        public readonly ?string $userKey = null,
        ?CarbonInterface $timestamp = null,
    ) {
        $this->timestamp = $timestamp ?? Carbon::now();
        $this->assertSafeProperties($properties);
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, scalar|null>
     */
    public static function filterAllowlisted(array $properties): array
    {
        $allowed = config('marketing.analytics.allowed_property_keys', []);
        $pattern = (string) config('marketing.analytics.forbidden_property_key_pattern');

        $filtered = [];

        foreach ($properties as $key => $value) {
            $key = (string) $key;

            if (preg_match($pattern, $key) === 1) {
                throw new InvalidArgumentException("Analytics property key [{$key}] is forbidden (PHI / clinical).");
            }

            $isUtm = str_starts_with($key, 'utm_');
            if (! $isUtm && ! in_array($key, $allowed, true)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function assertSafeProperties(array $properties): void
    {
        $pattern = (string) config('marketing.analytics.forbidden_property_key_pattern', '/symptom|diagnos|medication|phi|report|chat/i');
        $allowed = config('marketing.analytics.allowed_property_keys', []);

        foreach (array_keys($properties) as $key) {
            $key = (string) $key;

            if (preg_match($pattern, $key) === 1) {
                throw new InvalidArgumentException("Analytics property key [{$key}] is forbidden (PHI / clinical).");
            }

            $isUtm = str_starts_with($key, 'utm_');
            if (! $isUtm && ! in_array($key, $allowed, true)) {
                throw new InvalidArgumentException("Analytics property key [{$key}] is not allowlisted.");
            }
        }
    }
}
