<?php

namespace App\Actions\Modules;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\TenantSubscription;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class AssignPackageToTenantAction
{
    public function __construct(
        private SyncTenantEntitlementsFromPackageAction $syncEntitlements,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(Tenant $tenant, Package $package, bool $activateIncluded = true): TenantSubscription
    {
        return DB::transaction(function () use ($tenant, $package, $activateIncluded): TenantSubscription {
            $subscription = TenantSubscription::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'status' => TenantSubscription::STATUS_ACTIVE,
                ],
                [
                    'package_id' => $package->id,
                    'starts_at' => now(),
                    'renews_at' => now()->addMonth(),
                ],
            );

            TenantSubscription::query()->withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('id', '!=', $subscription->id)
                ->where('status', TenantSubscription::STATUS_ACTIVE)
                ->update(['status' => TenantSubscription::STATUS_CANCELLED]);

            $tenant->update(['plan_code' => $package->key]);

            $package->loadMissing(['modules', 'entitlements', 'limits']);

            if ($activateIncluded) {
                $includedModuleIds = [];
                foreach ($package->modules as $module) {
                    $inclusion = $module->pivot->inclusion ?? 'included';
                    if ($inclusion !== 'included') {
                        continue;
                    }

                    $includedModuleIds[] = $module->id;

                    TenantModule::query()->withoutGlobalScopes()->updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'module_id' => $module->id,
                        ],
                        [
                            'status' => TenantModule::STATUS_ACTIVE,
                            'source' => TenantModule::SOURCE_PACKAGE,
                            'activated_at' => now(),
                            'deactivated_at' => null,
                        ],
                    );
                }

                // Downgrade reconciliation: disable package-sourced modules no longer included.
                // Addon/manual installs are preserved.
                $orphanQuery = TenantModule::query()->withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('source', TenantModule::SOURCE_PACKAGE)
                    ->where('status', TenantModule::STATUS_ACTIVE);

                if ($includedModuleIds !== []) {
                    $orphanQuery->whereNotIn('module_id', $includedModuleIds);
                }

                $orphanQuery->update([
                    'status' => TenantModule::STATUS_DISABLED,
                    'deactivated_at' => now(),
                ]);
            }

            $this->syncEntitlements->handle($tenant, $package);

            $this->auditLogger->log('modules.package.assigned', $tenant, [
                'package_key' => $package->key,
                'subscription_id' => $subscription->id,
            ]);

            return $subscription->fresh(['package']);
        });
    }
}
