<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => null,
            'tenant_key' => 'system',
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'category' => fake()->optional()->randomElement(['lower_limb', 'upper_limb', 'core', 'balance']),
            'instructions' => fake()->optional()->paragraph(),
            'contraindications' => fake()->optional()->sentence(),
            'default_duration_seconds' => 60,
            'default_sets' => 3,
            'default_reps' => 10,
            'difficulty' => fake()->optional()->randomElement(['easy', 'moderate', 'hard']),
            'media_url' => null,
            'status' => 'active',
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'tenant_key' => (string) $tenant->id,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'tenant_key' => 'system',
        ]);
    }
}
