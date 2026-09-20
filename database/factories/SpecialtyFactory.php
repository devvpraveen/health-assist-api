<?php

namespace Database\Factories;

use App\Models\Specialty;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
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
            'description' => fake()->optional()->sentence(),
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
