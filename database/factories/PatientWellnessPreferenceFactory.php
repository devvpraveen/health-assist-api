<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientWellnessPreference;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatientWellnessPreference>
 */
class PatientWellnessPreferenceFactory extends Factory
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
            'interests' => ['sleep', 'hydration'],
            'goals' => ['feel_rested'],
            'excluded_tags' => [],
            'reminder_opt_in' => true,
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
