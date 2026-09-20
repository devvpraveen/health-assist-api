<?php

namespace Database\Seeders;

use App\Models\WellnessCategory;
use Illuminate\Database\Seeder;

class WellnessCategorySeeder extends Seeder
{
    /**
     * Seed platform wellness categories.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'sleep', 'name' => 'Sleep', 'description' => 'Rest and sleep hygiene habits.', 'sort_order' => 10],
            ['slug' => 'hydration', 'name' => 'Hydration', 'description' => 'Fluid intake and hydration habits.', 'sort_order' => 20],
            ['slug' => 'movement', 'name' => 'Movement', 'description' => 'Daily movement and mobility.', 'sort_order' => 30],
            ['slug' => 'nutrition', 'name' => 'Nutrition', 'description' => 'Everyday nutrition habits (non-clinical).', 'sort_order' => 40],
            ['slug' => 'stress_management', 'name' => 'Stress Management', 'description' => 'Stress reduction and coping skills.', 'sort_order' => 50],
            ['slug' => 'exercise', 'name' => 'Exercise', 'description' => 'General exercise lifestyle guidance.', 'sort_order' => 60],
            ['slug' => 'preventive_health', 'name' => 'Preventive Health', 'description' => 'Preventive wellness habits.', 'sort_order' => 70],
            ['slug' => 'general_wellness', 'name' => 'General Wellness', 'description' => 'Broad wellness education.', 'sort_order' => 80],
        ];

        foreach ($categories as $category) {
            WellnessCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
