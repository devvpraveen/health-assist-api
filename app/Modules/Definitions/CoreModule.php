<?php

namespace App\Modules\Definitions;

class CoreModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'core';
    }

    public function definition(): array
    {
        return [
            'name' => 'Health Assist Core',
            'description' => 'Auth, tenancy, RBAC, audit, and platform lifecycle.',
            'category' => 'platform',
        ];
    }

    public function dependencies(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return ['core.tenancy', 'core.auth', 'core.rbac', 'core.audit'];
    }

    public function navigation(): array
    {
        return ['profile', 'notifications', 'settings', 'dashboard'];
    }
}
