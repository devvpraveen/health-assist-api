<?php

namespace Database\Factories;

use App\Models\Experiment;
use App\Models\ExperimentAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExperimentAssignment> */
class ExperimentAssignmentFactory extends Factory
{
    protected $model = ExperimentAssignment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'experiment_id' => Experiment::factory(),
            'anonymous_id' => (string) fake()->uuid(),
            'user_id' => null,
            'variant_key' => 'control',
            'assigned_at' => now(),
        ];
    }
}
