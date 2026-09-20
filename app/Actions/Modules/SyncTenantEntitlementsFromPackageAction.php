<?php

namespace App\Actions\Modules;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantEntitlement;

class SyncTenantEntitlementsFromPackageAction
{
    public function handle(Tenant $tenant, Package $package): void
    {
        foreach ($package->entitlements as $entitlement) {
            TenantEntitlement::query()->withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'key' => $entitlement->key],
                [
                    'enabled' => $entitlement->enabled,
                    'limit_value' => null,
                    'period' => null,
                    'source' => 'package',
                    'meta' => $entitlement->meta,
                ],
            );
        }

        foreach ($package->limits as $limit) {
            TenantEntitlement::query()->withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'key' => $limit->key],
                [
                    'enabled' => true,
                    'limit_value' => $limit->value,
                    'period' => $limit->period,
                    'source' => 'package',
                    'meta' => $limit->meta,
                ],
            );
        }
    }
}
