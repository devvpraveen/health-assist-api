<?php

namespace App\Modules\Definitions;

class AiModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'ai';
    }

    public function definition(): array
    {
        return [
            'name' => 'AI Core',
            'description' => 'AI providers, prompts, model routing, and usage audit.',
            'category' => 'ai',
        ];
    }

    public function capabilities(): array
    {
        return ['ai.view', 'ai.run', 'ai.manage'];
    }

    public function permissions(): array
    {
        return ['ai.view', 'ai.run', 'ai.manage', 'tenant.ai.manage'];
    }

    public function navigation(): array
    {
        return ['ai_assistant'];
    }
}
