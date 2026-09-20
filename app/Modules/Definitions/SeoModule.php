<?php

namespace App\Modules\Definitions;

class SeoModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'seo';
    }

    public function definition(): array
    {
        return [
            'name' => 'SEO / AEO / GEO',
            'description' => 'Public educational SEO entities and FAQs.',
            'category' => 'growth',
        ];
    }

    public function capabilities(): array
    {
        return ['seo.view', 'seo.manage'];
    }

    public function permissions(): array
    {
        return ['seo.view', 'seo.manage'];
    }

    public function navigation(): array
    {
        return [];
    }
}
