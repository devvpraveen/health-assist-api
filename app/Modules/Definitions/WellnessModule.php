<?php

namespace App\Modules\Definitions;

class WellnessModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'wellness';
    }

    public function definition(): array
    {
        return [
            'name' => 'Wellness',
            'description' => 'Educational wellness content and preferences.',
            'category' => 'engagement',
        ];
    }

    public function capabilities(): array
    {
        return ['wellness.view', 'wellness.manage'];
    }

    public function permissions(): array
    {
        return ['wellness.view', 'wellness.manage'];
    }

    public function navigation(): array
    {
        return ['home'];
    }
}
