<?php

namespace App\Services\Modules;

use App\Exceptions\Modules\EntitlementLimitExceededException;
use App\Models\TenantEntitlement;
use App\Models\UsageCounter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * SaaS package entitlement usage (usage_counters + tenant_entitlements).
 * Separate from AI provider metering (ai_usage_records) but both may apply.
 */
class EntitlementUsageService
{
    public const KEY_AI_MESSAGES = 'ai_messages_month';

    public const KEY_AI_REPORTS = 'ai_reports_month';

    public function currentPeriodKey(?CarbonImmutable $at = null): string
    {
        return ($at ?? CarbonImmutable::now())->format('Y-m');
    }

    /**
     * @return array{enabled: bool, limit_value: int|null, period: string|null, source: string}|null
     */
    public function entitlement(int $tenantId, string $key): ?array
    {
        $row = TenantEntitlement::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'enabled' => (bool) $row->enabled,
            'limit_value' => $row->limit_value,
            'period' => $row->period,
            'source' => $row->source,
        ];
    }

    public function used(int $tenantId, string $key, ?string $periodKey = null): int
    {
        $periodKey ??= $this->currentPeriodKey();

        return (int) UsageCounter::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->where('period_key', $periodKey)
            ->value('count');
    }

    /**
     * @throws EntitlementLimitExceededException
     */
    public function assertWithinLimit(int $tenantId, string $key, int $delta = 1): void
    {
        $entitlement = $this->entitlement($tenantId, $key);
        if ($entitlement === null) {
            return;
        }

        if (! $entitlement['enabled']) {
            throw new EntitlementLimitExceededException(
                "Entitlement [{$key}] is disabled for this organization.",
                [
                    'key' => $key,
                    'period_key' => $this->currentPeriodKey(),
                    'used' => $this->used($tenantId, $key),
                    'limit' => 0,
                    'remaining' => 0,
                ],
            );
        }

        $limit = $entitlement['limit_value'];
        if ($limit === null) {
            return; // unlimited
        }

        $periodKey = $this->currentPeriodKey();
        $used = $this->used($tenantId, $key, $periodKey);

        if (($used + $delta) > $limit) {
            throw new EntitlementLimitExceededException(
                "Package entitlement [{$key}] exceeded for this organization.",
                [
                    'key' => $key,
                    'period_key' => $periodKey,
                    'used' => $used,
                    'limit' => $limit,
                    'remaining' => max(0, $limit - $used),
                ],
            );
        }
    }

    public function increment(int $tenantId, string $key, int $delta = 1): UsageCounter
    {
        $periodKey = $this->currentPeriodKey();

        return DB::transaction(function () use ($tenantId, $key, $periodKey, $delta): UsageCounter {
            $row = UsageCounter::query()->withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'period_key' => $periodKey,
                ],
                ['count' => 0],
            );

            UsageCounter::query()->withoutGlobalScopes()
                ->where('id', $row->id)
                ->increment('count', $delta);

            return $row->refresh();
        });
    }

    /**
     * Assert then increment atomically for a successful billable action.
     *
     * @throws EntitlementLimitExceededException
     */
    public function consume(int $tenantId, string $key, int $delta = 1): UsageCounter
    {
        return DB::transaction(function () use ($tenantId, $key, $delta): UsageCounter {
            $this->assertWithinLimit($tenantId, $key, $delta);

            return $this->increment($tenantId, $key, $delta);
        });
    }
}
