<?php

namespace Database\Factories;

use App\Models\ClinicalExercisePlan;
use App\Models\ClinicalExercisePlanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicalExercisePlanItem>
 */
class ClinicalExercisePlanItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exercise_plan_id' => ClinicalExercisePlan::factory(),
            'exercise_id' => null,
            'custom_name' => fake()->words(2, true),
            'frequency' => 'daily',
            'sets' => 3,
            'reps' => 10,
            'duration_seconds' => null,
            'instructions' => null,
            'sort_order' => 0,
        ];
    }

    public function forPlan(ClinicalExercisePlan $plan): static
    {
        return $this->state(fn (array $attributes) => [
            'exercise_plan_id' => $plan->id,
        ]);
    }
}
