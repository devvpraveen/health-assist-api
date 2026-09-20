<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
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
            'provider_id' => null,
            'name' => fake()->randomElement(['Metformin', 'Lisinopril', 'Atorvastatin', 'Amoxicillin']),
            'dosage' => fake()->randomElement(['500mg', '10mg', '20mg', '250mg']),
            'frequency_label' => 'twice daily',
            'route' => 'oral',
            'instructions' => 'Take with food.',
            'notes' => null,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'status' => Medication::STATUS_ACTIVE,
            'created_by_user_id' => null,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
            'tenant_id' => $provider->tenant_id,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Medication::STATUS_ACTIVE,
        ]);
    }
}
