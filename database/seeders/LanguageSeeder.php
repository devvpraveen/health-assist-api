<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Services\I18n\LanguageCatalog;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Seed the platform language catalog and default scope assignments.
     */
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_rtl' => false, 'is_enabled' => true, 'sort_order' => 1],
            ['code' => 'hi', 'name' => 'Hindi', 'native_name' => 'हिन्दी', 'is_rtl' => false, 'is_enabled' => true, 'sort_order' => 2],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'is_rtl' => false, 'is_enabled' => false, 'sort_order' => 3],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'is_rtl' => true, 'is_enabled' => false, 'sort_order' => 4],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_rtl' => false, 'is_enabled' => false, 'sort_order' => 5],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_rtl' => false, 'is_enabled' => false, 'sort_order' => 6],
        ];

        foreach ($languages as $languageData) {
            $language = Language::query()->updateOrCreate(
                ['code' => $languageData['code']],
                $languageData,
            );

            if (! $language->is_enabled) {
                continue;
            }

            foreach (LanguageCatalog::SCOPES as $scope) {
                $language->scopeAssignments()->updateOrCreate(
                    ['scope' => $scope],
                    ['is_enabled' => true],
                );
            }
        }
    }
}
