<?php

namespace App\Modules\Definitions;

class CrmModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'crm';
    }

    public function definition(): array
    {
        return [
            'name' => 'CRM',
            'description' => 'Marketing CRM leads and follow-up.',
            'category' => 'growth',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'marketing'];
    }

    public function capabilities(): array
    {
        return ['crm.manage'];
    }

    public function permissions(): array
    {
        return ['crm.manage'];
    }

    public function navigation(): array
    {
        return ['crm'];
    }
}
