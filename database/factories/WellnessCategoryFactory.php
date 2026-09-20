<?php

namespace Database\Factories;

use App\Models\WellnessCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WellnessCategory>
 */
class WellnessCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
