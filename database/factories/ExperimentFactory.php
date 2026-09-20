<?php

namespace Database\Factories;

use App\Models\Experiment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Experiment> */
class ExperimentFactory extends Factory
{
    protected $model = Experiment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $key = 'exp_'.Str::random(6);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'tenant_key' => 'tenant:pending',
            'key' => $key,
            'name' => 'Experiment '.$key,
            'status' => Experiment::STATUS_DRAFT,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Experiment $experiment): void {
            if ($experiment->tenant_id && $experiment->tenant_key === 'tenant:pending') {
                $experiment->tenant_key = 'tenant:'.$experiment->tenant_id;
            }
        })->afterCreating(function (Experiment $experiment): void {
            if ($experiment->tenant_key === 'tenant:pending' && $experiment->tenant_id) {
                $experiment->forceFill(['tenant_key' => 'tenant:'.$experiment->tenant_id])->saveQuietly();
            }
        });
    }

    public function running(): static
    {
        return $this->state(fn () => ['status' => Experiment::STATUS_RUNNING]);
    }
}
