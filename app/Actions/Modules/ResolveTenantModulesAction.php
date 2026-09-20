<?php

namespace App\Actions\Modules;

use App\Models\Tenant;
use App\Services\Modules\ModuleDependencyEngine;

class ResolveTenantModulesAction
{
    public function __construct(private ModuleDependencyEngine $dependencies) {}

    /**
     * @return list<string>
     */
    public function handle(Tenant|int $tenant): array
    {
        return $this->dependencies->activeKeysForTenant($tenant);
    }

    public function isActive(Tenant|int $tenant, string $moduleKey): bool
    {
        return in_array($moduleKey, $this->handle($tenant), true);
    }
}
