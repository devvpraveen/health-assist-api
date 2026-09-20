<?php

namespace App\Services\AI;

use App\Exceptions\AI\AiQuotaExceededException;
use App\Exceptions\Modules\EntitlementLimitExceededException;
use App\Models\AiUsageRecord;
use App\Models\Tenant;
use App\Services\Modules\EntitlementUsageService;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

class AiUsageQuotaService
{
    public function __construct(
        private readonly EntitlementUsageService $entitlementUsage,
    ) {}

    /**
     * @return array{
     *     period: string,
     *     period_start: string,
     *     period_end: string,
     *     requests_used: int,
     *     requests_limit: int|null,
     *     requests_remaining: int|null,
     *     tokens_used: int,
     *     tokens_limit: int|null,
     *     tokens_remaining: int|null,
     *     estimated_cost_cents: int,
     *     enforce_quotas: bool,
     *     provider: string,
     *     package_ai_messages_limit: int|null,
     *     package_ai_messages_used: int
     * }
     */
    public function summary(?int $tenantId = null): array
    {
        $tenantId ??= TenantContext::id();
        $periodStart = CarbonImmutable::now()->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();
        $limits = $this->limitsForTenant($tenantId);

        $query = AiUsageRecord::query()
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $periodEnd);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $requestsUsed = (int) (clone $query)->count();
        $tokensUsed = (int) (clone $query)->selectRaw('COALESCE(SUM(input_tokens),0) + COALESCE(SUM(output_tokens),0) as total')->value('total');
        $costCents = (int) (clone $query)->sum('estimated_cost_cents');

        $requestsLimit = $limits['monthly_request_limit'];
        $tokensLimit = $limits['monthly_token_limit'];

        $packageLimit = null;
        $packageUsed = 0;
        if ($tenantId !== null) {
            $ent = $this->entitlementUsage->entitlement($tenantId, EntitlementUsageService::KEY_AI_MESSAGES);
            if ($ent !== null && $ent['enabled']) {
                $packageLimit = $ent['limit_value'];
                $packageUsed = $this->entitlementUsage->used($tenantId, EntitlementUsageService::KEY_AI_MESSAGES);
            }
        }

        // Effective request limit is the tighter of config quota and package entitlement.
        if ($packageLimit !== null) {
            $requestsLimit = $requestsLimit === null
                ? $packageLimit
                : min($requestsLimit, $packageLimit);
        }

        return [
            'period' => $periodStart->format('Y-m'),
            'period_start' => $periodStart->toIso8601String(),
            'period_end' => $periodEnd->toIso8601String(),
            'requests_used' => max($requestsUsed, $packageUsed),
            'requests_limit' => $requestsLimit,
            'requests_remaining' => $requestsLimit === null ? null : max(0, $requestsLimit - max($requestsUsed, $packageUsed)),
            'tokens_used' => $tokensUsed,
            'tokens_limit' => $tokensLimit,
            'tokens_remaining' => $tokensLimit === null ? null : max(0, $tokensLimit - $tokensUsed),
            'estimated_cost_cents' => $costCents,
            'enforce_quotas' => (bool) config('ai.metering.enforce_quotas', true),
            'provider' => (string) config('ai.default_provider', 'mock'),
            'package_ai_messages_limit' => $packageLimit,
            'package_ai_messages_used' => $packageUsed,
        ];
    }

    /**
     * @throws AiQuotaExceededException
     */
    public function assertWithinQuota(?int $tenantId = null): void
    {
        if (! config('ai.metering.enabled', true) || ! config('ai.metering.enforce_quotas', true)) {
            return;
        }

        $tenantId ??= TenantContext::id();

        if ($tenantId !== null) {
            try {
                $this->entitlementUsage->assertWithinLimit($tenantId, EntitlementUsageService::KEY_AI_MESSAGES);
            } catch (EntitlementLimitExceededException $e) {
                throw new AiQuotaExceededException(
                    $e->getMessage(),
                    [
                        'period' => $e->snapshot['period_key'],
                        'requests_used' => $e->snapshot['used'],
                        'requests_limit' => $e->snapshot['limit'],
                        'tokens_used' => 0,
                        'tokens_limit' => null,
                        'reason' => 'package_entitlement',
                    ],
                    429,
                );
            }
        }

        $snapshot = $this->summary($tenantId);
        $configLimit = $this->limitsForTenant($tenantId)['monthly_request_limit'];

        $recordsUsed = $tenantId === null
            ? (int) $snapshot['requests_used']
            : (int) AiUsageRecord::query()
                ->where('tenant_id', $tenantId)
                ->where('created_at', '>=', CarbonImmutable::now()->startOfMonth())
                ->count();

        if ($configLimit !== null && $recordsUsed >= $configLimit) {
            throw new AiQuotaExceededException(
                'Monthly AI request quota exceeded for this organization.',
                [
                    'period' => $snapshot['period'],
                    'requests_used' => $recordsUsed,
                    'requests_limit' => $configLimit,
                    'tokens_used' => $snapshot['tokens_used'],
                    'tokens_limit' => $snapshot['tokens_limit'],
                    'reason' => 'requests',
                ],
            );
        }

        if (
            $snapshot['tokens_limit'] !== null
            && $snapshot['tokens_used'] >= $snapshot['tokens_limit']
        ) {
            throw new AiQuotaExceededException(
                'Monthly AI token quota exceeded for this organization.',
                [
                    'period' => $snapshot['period'],
                    'requests_used' => $snapshot['requests_used'],
                    'requests_limit' => $snapshot['requests_limit'],
                    'tokens_used' => $snapshot['tokens_used'],
                    'tokens_limit' => $snapshot['tokens_limit'],
                    'reason' => 'tokens',
                ],
            );
        }
    }

    public function recordPackageMessageUsage(?int $tenantId): void
    {
        if ($tenantId === null) {
            return;
        }

        $this->entitlementUsage->increment($tenantId, EntitlementUsageService::KEY_AI_MESSAGES);
    }

    public function estimateCostCents(int $inputTokens, int $outputTokens, ?string $model = null): int
    {
        $pricing = (array) config('ai.metering.pricing', []);
        $modelPricing = is_array($pricing['models'] ?? null) ? $pricing['models'] : [];
        $row = is_array($modelPricing[$model ?? ''] ?? null)
            ? $modelPricing[$model]
            : [
                'input_per_1m_cents' => $pricing['default_input_per_1m_cents'] ?? 15,
                'output_per_1m_cents' => $pricing['default_output_per_1m_cents'] ?? 60,
            ];

        $inputRate = (float) ($row['input_per_1m_cents'] ?? 15);
        $outputRate = (float) ($row['output_per_1m_cents'] ?? 60);

        $cents = ($inputTokens / 1_000_000) * $inputRate
            + ($outputTokens / 1_000_000) * $outputRate;

        return (int) max(0, (int) ceil($cents));
    }

    /**
     * @return array{monthly_request_limit: int|null, monthly_token_limit: int|null}
     */
    private function limitsForTenant(?int $tenantId): array
    {
        $requests = config('ai.metering.monthly_request_limit');
        $tokens = config('ai.metering.monthly_token_limit');

        $requestsLimit = $requests === null || $requests === '' ? null : (int) $requests;
        $tokensLimit = $tokens === null || $tokens === '' ? null : (int) $tokens;

        if ($tenantId !== null) {
            $tenant = Tenant::query()->with('settings')->find($tenantId);
            $settings = $tenant?->settings?->settings ?? [];
            $overrideRequests = Arr::get($settings, 'ai.quotas.monthly_request_limit');
            $overrideTokens = Arr::get($settings, 'ai.quotas.monthly_token_limit');

            if ($overrideRequests !== null && $overrideRequests !== '') {
                $requestsLimit = (int) $overrideRequests;
            }
            if ($overrideTokens !== null && $overrideTokens !== '') {
                $tokensLimit = (int) $overrideTokens;
            }
        }

        return [
            'monthly_request_limit' => $requestsLimit,
            'monthly_token_limit' => $tokensLimit,
        ];
    }
}
