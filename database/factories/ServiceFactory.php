<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'clinic_id' => fn (array $attributes) => Clinic::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'specialty_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'duration_minutes' => 30,
            'price_cents' => fake()->optional()->numberBetween(50000, 500000),
            'currency' => 'INR',
            'is_public' => true,
            'status' => 'active',
        ];
    }

    public function forClinic(Clinic $clinic): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
        ]);
    }

    public function forSpecialty(Specialty $specialty): static
    {
        return $this->state(fn (array $attributes) => [
            'specialty_id' => $specialty->id,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'status' => 'active',
        ]);
    }
}
