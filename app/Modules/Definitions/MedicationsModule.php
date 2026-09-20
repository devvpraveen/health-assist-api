<?php

namespace App\Modules\Definitions;

class MedicationsModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'medications';
    }

    public function definition(): array
    {
        return [
            'name' => 'Medications',
            'description' => 'Medication tracking, schedules, and adherence logs (no AI prescribing).',
            'category' => 'clinical',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'patients'];
    }

    public function capabilities(): array
    {
        return ['medications.view', 'medications.manage'];
    }

    public function permissions(): array
    {
        return ['medications.view', 'medications.manage'];
    }

    public function navigation(): array
    {
        return ['medications'];
    }
}
