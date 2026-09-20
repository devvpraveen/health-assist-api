<?php

namespace App\Modules\Definitions;

class HealthGuideModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'health_guide';
    }

    public function definition(): array
    {
        return [
            'name' => 'AI Health Guide',
            'description' => 'Progressive patient journey, safety-aware assistive chat, and booking assist.',
            'category' => 'ai',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'patients', 'ai'];
    }

    public function optionalDependencies(): array
    {
        return ['appointments', 'providers'];
    }

    public function capabilities(): array
    {
        return ['health_guide.view', 'health_guide.run'];
    }

    public function permissions(): array
    {
        return ['health_guide.view', 'health_guide.run'];
    }

    public function navigation(): array
    {
        return ['ai_assistant'];
    }
}
