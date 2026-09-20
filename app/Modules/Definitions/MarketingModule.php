<?php

namespace App\Modules\Definitions;

class MarketingModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'marketing';
    }

    public function definition(): array
    {
        return [
            'name' => 'Marketing',
            'description' => 'Attribution, referrals, segments, and email workflows.',
            'category' => 'growth',
        ];
    }

    public function capabilities(): array
    {
        return ['marketing.view', 'marketing.manage'];
    }

    public function permissions(): array
    {
        return ['marketing.view', 'marketing.manage', 'experiments.manage'];
    }

    public function navigation(): array
    {
        return ['marketing'];
    }
}
