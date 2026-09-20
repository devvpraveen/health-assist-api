<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->optional()->date(),
            'gender' => fake()->optional()->randomElement(['male', 'female', 'other', 'unknown']),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address_line1' => fake()->optional()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'country' => fake()->optional()->countryCode(),
            'preferred_language' => 'en',
            'communication_preferences' => [
                'email' => true,
                'sms' => false,
                'whatsapp' => false,
                'push' => false,
            ],
            'consent_status' => 'pending',
            'consent_at' => null,
            'profile_photo_path' => null,
            'status' => 'active',
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
        ]);
    }
}
