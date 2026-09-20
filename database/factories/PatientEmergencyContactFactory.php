<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PatientEmergencyContact>
 */
class PatientEmergencyContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(['spouse', 'parent', 'sibling', 'friend', 'other']),
            'phone' => fake()->e164PhoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'is_primary' => false,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
