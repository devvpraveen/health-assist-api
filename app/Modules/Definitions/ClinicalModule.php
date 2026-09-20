<?php

namespace App\Modules\Definitions;

class ClinicalModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'clinical';
    }

    public function definition(): array
    {
        return [
            'name' => 'Clinical Documentation',
            'description' => 'Assessments, SOAP notes, treatment plans, and discharge.',
            'category' => 'clinical',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'patients'];
    }

    public function capabilities(): array
    {
        return ['clinical.notes.view', 'clinical.treatment.view'];
    }

    public function permissions(): array
    {
        return [
            'clinical.assessments.view',
            'clinical.assessments.manage',
            'clinical.notes.view',
            'clinical.notes.manage',
            'clinical.treatment.view',
            'clinical.treatment.manage',
            'clinical.progress.view',
            'clinical.progress.manage',
            'clinical.discharge.view',
            'clinical.discharge.manage',
            'clinical.exercises.view',
            'clinical.exercises.manage',
        ];
    }

    public function navigation(): array
    {
        return ['clinical_notes', 'soap_notes', 'treatment_plans', 'exercise_plans', 'treatment', 'dashboard'];
    }
}
