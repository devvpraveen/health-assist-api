<?php

namespace App\Modules\Definitions;

use App\Modules\Contracts\HealthAssistModule;

abstract class AbstractModuleDefinition implements HealthAssistModule
{
    abstract public function key(): string;

    abstract public function definition(): array;

    public function dependencies(): array
    {
        return ['core'];
    }

    public function optionalDependencies(): array
    {
        return [];
    }

    public function conflicts(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return [];
    }

    public function permissions(): array
    {
        return [];
    }

    public function navigation(): array
    {
        return [];
    }

    public function routeModuleKeys(): array
    {
        return [$this->key()];
    }

    /**
     * @return array<string, mixed>
     */
    public function toRegistryRow(): array
    {
        $meta = $this->definition();

        return [
            'key' => $this->key(),
            'name' => $meta['name'],
            'slug' => $meta['slug'] ?? $this->key(),
            'description' => $meta['description'] ?? null,
            'category' => $meta['category'] ?? 'clinical',
            'version' => $meta['version'] ?? '1.0.0',
            'status' => 'active',
            'dependencies' => $this->dependencies(),
            'optional_dependencies' => $this->optionalDependencies(),
            'conflicts' => $this->conflicts(),
            'capabilities' => $this->capabilities(),
            'configuration_schema' => $meta['configuration_schema'] ?? [],
            'settings_schema' => $meta['settings_schema'] ?? [],
            'metadata' => array_merge($meta['metadata'] ?? [], [
                'permissions' => $this->permissions(),
                'navigation' => $this->navigation(),
                'route_module_keys' => $this->routeModuleKeys(),
            ]),
        ];
    }
}
