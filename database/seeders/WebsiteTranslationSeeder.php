<?php

namespace Database\Seeders;

use App\Services\I18n\TranslationCatalog;
use App\Services\I18n\WebsiteUiCatalog;
use Illuminate\Database\Seeder;

class WebsiteTranslationSeeder extends Seeder
{
    /**
     * Seed English (+ Hindi where defined) UI strings for portal and mobile.
     */
    public function run(): void
    {
        $rows = [];

        foreach (WebsiteUiCatalog::english() as $key => $value) {
            $rows[] = ['group' => 'website', 'key' => $key, 'locale' => 'en', 'value' => $value];
        }

        foreach (WebsiteUiCatalog::hindi() as $key => $value) {
            $rows[] = ['group' => 'website', 'key' => $key, 'locale' => 'hi', 'value' => $value];
        }

        app(TranslationCatalog::class)->upsertMany($rows);
    }
}
