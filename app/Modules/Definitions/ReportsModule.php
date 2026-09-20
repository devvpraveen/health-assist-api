<?php

namespace App\Modules\Definitions;

class ReportsModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'reports';
    }

    public function definition(): array
    {
        return [
            'name' => 'Report AI',
            'description' => 'Report analysis, review, and educational explanations.',
            'category' => 'ai',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'patients', 'ai'];
    }

    public function capabilities(): array
    {
        return ['reports.view', 'reports.analyze', 'reports.review'];
    }

    public function permissions(): array
    {
        return ['reports.view', 'reports.analyze', 'reports.review'];
    }

    public function navigation(): array
    {
        return ['reports'];
    }
}
