<?php

namespace App\Actions\Modules;

use App\Models\Tenant;
use App\Models\TenantEntitlement;

class ResolveEntitlementsAction
{
    /**
     * @return array<string, array{enabled: bool, limit_value: int|null, period: string|null, source: string}>
     */
    public function handle(Tenant|int $tenant): array
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        $rows = TenantEntitlement::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[$row->key] = [
                'enabled' => (bool) $row->enabled,
                'limit_value' => $row->limit_value,
                'period' => $row->period,
                'source' => $row->source,
            ];
        }

        return $out;
    }
}
