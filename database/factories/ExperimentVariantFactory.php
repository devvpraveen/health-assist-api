<?php

namespace Database\Factories;

use App\Models\Experiment;
use App\Models\ExperimentVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExperimentVariant> */
class ExperimentVariantFactory extends Factory
{
    protected $model = ExperimentVariant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'experiment_id' => Experiment::factory(),
            'key' => 'control',
            'weight' => 50,
            'payload' => null,
        ];
    }
}
