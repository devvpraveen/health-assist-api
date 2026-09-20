<?php

namespace App\Actions\Modules;

use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Services\AuditLogger;
use App\Services\Modules\ModuleDependencyEngine;

class DeactivateModuleAction
{
    public function __construct(
        private ModuleDependencyEngine $dependencies,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(Tenant $tenant, string $moduleKey): TenantModule
    {
        $this->dependencies->assertCanDeactivate($moduleKey, $tenant);

        $module = PlatformModule::query()->where('key', $moduleKey)->firstOrFail();
        $row = TenantModule::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->first();

        if ($row === null) {
            $row = TenantModule::query()->withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'status' => TenantModule::STATUS_DISABLED,
                'source' => TenantModule::SOURCE_MANUAL,
                'deactivated_at' => now(),
            ]);
        } else {
            $row->update([
                'status' => TenantModule::STATUS_DISABLED,
                'deactivated_at' => now(),
            ]);
        }

        // Historical healthcare data is intentionally retained.
        $this->auditLogger->log('modules.deactivated', $tenant, [
            'module_key' => $moduleKey,
            'data_retained' => true,
        ]);

        return $row->fresh('module');
    }
}
