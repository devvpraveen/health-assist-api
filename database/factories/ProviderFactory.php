<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'clinic_id' => fn (array $attributes) => Clinic::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => "Dr. {$firstName} {$lastName}",
            'type' => fake()->randomElement(Provider::TYPES),
            'bio' => fake()->optional()->paragraph(),
            'photo_path' => null,
            'years_experience' => fake()->optional()->numberBetween(1, 35),
            'languages' => ['en'],
            'license_number' => fake()->optional()->bothify('LIC-####'),
            'verification_status' => 'pending',
            'is_public' => false,
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

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'clinic_id' => Clinic::factory()->create(['tenant_id' => $tenant->id])->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
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
