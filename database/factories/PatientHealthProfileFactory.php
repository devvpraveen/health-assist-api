<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientHealthProfile;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientHealthProfile>
 */
class PatientHealthProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'medical_history' => fake()->optional()->paragraph(),
            'conditions' => fake()->optional()->randomElements(['hypertension', 'diabetes', 'asthma'], 2),
            'allergies' => fake()->optional()->randomElements(['penicillin', 'peanuts'], 1),
            'medications' => null,
            'previous_treatments' => null,
            'surgeries' => null,
            'family_history' => null,
            'lifestyle' => null,
            'emergency_information' => null,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }
}
