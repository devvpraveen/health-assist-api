<?php

namespace App\Modules\Definitions;

class PatientsModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'patients';
    }

    public function definition(): array
    {
        return [
            'name' => 'Patients',
            'description' => 'Patient profiles, records, documents, and timeline.',
            'category' => 'clinical',
        ];
    }

    public function capabilities(): array
    {
        return ['patients.view', 'patients.manage', 'patients.records.view'];
    }

    public function permissions(): array
    {
        return ['patients.view', 'patients.manage', 'patients.records.view', 'patients.records.manage', 'patients.documents.view', 'patients.documents.manage'];
    }

    public function navigation(): array
    {
        return ['patients', 'records', 'home', 'dashboard'];
    }
}
