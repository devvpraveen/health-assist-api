<?php

namespace App\Actions\Marketing;

use App\Contracts\Marketing\AnalyticsGatewayInterface;
use App\Models\User;
use App\Services\Marketing\Analytics\AnalyticsEvent;
use App\Services\Marketing\Analytics\AnalyticsUserKey;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TrackAnalyticsEventAction
{
    public function __construct(private AnalyticsGatewayInterface $analytics) {}

    /**
     * @param  array{name: string, anonymous_id: string, properties?: array<string, mixed>, tenant_id?: int|null}  $data
     */
    public function handle(array $data, ?User $user = null): void
    {
        try {
            $properties = AnalyticsEvent::filterAllowlisted($data['properties'] ?? []);

            // Re-validate: reject if original payload had forbidden keys (filter throws).
            $raw = $data['properties'] ?? [];
            $pattern = (string) config('marketing.analytics.forbidden_property_key_pattern');
            foreach (array_keys($raw) as $key) {
                if (preg_match($pattern, (string) $key) === 1) {
                    throw new InvalidArgumentException("Analytics property key [{$key}] is forbidden (PHI / clinical).");
                }
            }

            $event = new AnalyticsEvent(
                name: $data['name'],
                anonymousId: $data['anonymous_id'],
                properties: $properties,
                tenantId: $data['tenant_id'] ?? $user?->tenant_id,
                userKey: $user ? AnalyticsUserKey::fromUser($user) : null,
                timestamp: Carbon::now(),
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'properties' => $e->getMessage(),
            ]);
        }

        $this->analytics->track($event);
    }
}
