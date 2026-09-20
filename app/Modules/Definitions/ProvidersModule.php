<?php

namespace App\Modules\Definitions;

class ProvidersModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'providers';
    }

    public function definition(): array
    {
        return [
            'name' => 'Providers & Clinics',
            'description' => 'Clinics, providers, specialties, services, and schedules.',
            'category' => 'operations',
        ];
    }

    public function capabilities(): array
    {
        return ['providers.view', 'clinics.view', 'schedules.view'];
    }

    public function permissions(): array
    {
        return ['providers.view', 'providers.manage', 'clinics.view', 'clinics.manage', 'specialties.view', 'services.view', 'schedules.view', 'schedules.manage'];
    }

    public function navigation(): array
    {
        return ['providers', 'dashboard'];
    }
}
