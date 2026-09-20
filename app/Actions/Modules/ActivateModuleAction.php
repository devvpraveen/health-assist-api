<?php

namespace App\Actions\Modules;

use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\TenantSubscription;
use App\Services\AuditLogger;
use App\Services\Modules\ModuleDependencyEngine;
use RuntimeException;

class ActivateModuleAction
{
    public function __construct(
        private ModuleDependencyEngine $dependencies,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(Tenant $tenant, string $moduleKey, string $source = TenantModule::SOURCE_MANUAL): TenantModule
    {
        $this->dependencies->assertCanActivate($moduleKey, $tenant);

        $module = PlatformModule::query()->where('key', $moduleKey)->firstOrFail();
        $package = TenantSubscription::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', TenantSubscription::STATUS_ACTIVE)
            ->with('package.modules')
            ->first()
            ?->package;

        if ($package) {
            $pivot = $package->modules->firstWhere('id', $module->id);
            $inclusion = $pivot?->pivot?->inclusion;
            if ($pivot === null) {
                throw new RuntimeException("Module [{$moduleKey}] is not available on package [{$package->key}].");
            }
            if ($inclusion === 'addon_eligible') {
                $source = TenantModule::SOURCE_ADDON;
            } elseif ($inclusion === 'included') {
                $source = TenantModule::SOURCE_PACKAGE;
            }
        }

        $row = TenantModule::query()->withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
            ],
            [
                'status' => TenantModule::STATUS_ACTIVE,
                'source' => $source,
                'activated_at' => now(),
                'deactivated_at' => null,
            ],
        );

        $this->auditLogger->log('modules.activated', $tenant, [
            'module_key' => $moduleKey,
            'source' => $source,
        ]);

        return $row->load('module');
    }
}
