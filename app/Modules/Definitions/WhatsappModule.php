<?php

namespace App\Modules\Definitions;

class WhatsappModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'whatsapp';
    }

    public function definition(): array
    {
        return [
            'name' => 'WhatsApp AI',
            'description' => 'WhatsApp conversations, handoffs, and messaging automations.',
            'category' => 'engagement',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'patients'];
    }

    public function optionalDependencies(): array
    {
        return ['ai', 'health_guide'];
    }

    public function capabilities(): array
    {
        return ['whatsapp.view', 'whatsapp.manage'];
    }

    public function permissions(): array
    {
        return ['whatsapp.view', 'whatsapp.manage'];
    }

    public function navigation(): array
    {
        return ['messages'];
    }
}
