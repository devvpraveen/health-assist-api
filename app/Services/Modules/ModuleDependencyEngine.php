<?php

namespace App\Services\Modules;

use App\Models\PlatformModule;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class ModuleDependencyEngine
{
    public function __construct(private ModuleDefinitionRegistry $definitions) {}

    /**
     * @param  list<string>  $desiredActiveKeys
     * @return list<string> Missing dependency keys
     */
    public function missingDependencies(string $moduleKey, array $desiredActiveKeys): array
    {
        $module = $this->definitions->get($moduleKey);
        $missing = [];

        foreach ($module->dependencies() as $dependency) {
            if (! in_array($dependency, $desiredActiveKeys, true)) {
                $missing[] = $dependency;
            }
        }

        return $missing;
    }

    /**
     * @param  list<string>  $desiredActiveKeys
     * @return list<string> Conflicting module keys already active
     */
    public function activeConflicts(string $moduleKey, array $desiredActiveKeys): array
    {
        $module = $this->definitions->get($moduleKey);
        $hits = [];

        foreach ($module->conflicts() as $conflict) {
            if (in_array($conflict, $desiredActiveKeys, true)) {
                $hits[] = $conflict;
            }
        }

        return $hits;
    }

    /**
     * Modules that currently depend on $moduleKey and are active.
     *
     * @param  list<string>  $activeKeys
     * @return list<string>
     */
    public function activeDependents(string $moduleKey, array $activeKeys): array
    {
        $dependents = [];

        foreach ($activeKeys as $key) {
            if ($key === $moduleKey) {
                continue;
            }
            $deps = $this->definitions->get($key)->dependencies();
            if (in_array($moduleKey, $deps, true)) {
                $dependents[] = $key;
            }
        }

        return $dependents;
    }

    /**
     * @return list<string>
     */
    public function activeKeysForTenant(Tenant|int $tenant): array
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return TenantModule::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', TenantModule::STATUS_ACTIVE)
            ->with('module')
            ->get()
            ->map(fn (TenantModule $row) => $row->module?->key)
            ->filter()
            ->values()
            ->all();
    }

    public function assertCanActivate(string $moduleKey, Tenant|int $tenant): void
    {
        if (! $this->definitions->has($moduleKey)) {
            throw new InvalidArgumentException("Unknown module [{$moduleKey}].");
        }

        $active = $this->activeKeysForTenant($tenant);
        $projected = array_values(array_unique([...$active, $moduleKey]));

        $missing = $this->missingDependencies($moduleKey, $projected);
        if ($missing !== []) {
            throw new RuntimeException(
                'Cannot activate ['.$moduleKey.']: missing dependencies: '.implode(', ', $missing)
            );
        }

        $conflicts = $this->activeConflicts($moduleKey, $active);
        if ($conflicts !== []) {
            throw new RuntimeException(
                'Cannot activate ['.$moduleKey.']: conflicts with active modules: '.implode(', ', $conflicts)
            );
        }
    }

    public function assertCanDeactivate(string $moduleKey, Tenant|int $tenant): void
    {
        if ($moduleKey === 'core') {
            throw new RuntimeException('The core module cannot be deactivated.');
        }

        $active = $this->activeKeysForTenant($tenant);
        $dependents = $this->activeDependents($moduleKey, $active);

        if ($dependents !== []) {
            throw new RuntimeException(
                'Cannot deactivate ['.$moduleKey.'] because these active modules depend on it: '.implode(', ', $dependents)
            );
        }
    }

    /**
     * @return Collection<int, PlatformModule>
     */
    public function catalog(): Collection
    {
        return PlatformModule::query()->orderBy('key')->get();
    }
}
