<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            LanguageSeeder::class,
            SpecialtySeeder::class,
            ExerciseSeeder::class,
            AiCoreSeeder::class,
            SafetyRulesSeeder::class,
            WellnessCategorySeeder::class,
            WellnessContentSeeder::class,
            DemoTenantSeeder::class,
            ModulePlatformSeeder::class,
            DynamicTemplatesFormsSeeder::class,
            AutomationWorkflowSeeder::class,
            SeoContentSeeder::class,
        ]);
    }
}
