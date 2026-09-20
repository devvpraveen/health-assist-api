<?php

namespace Database\Factories;

use App\Models\ClinicalTreatmentSession;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalTreatmentSession>
 */
class ClinicalTreatmentSessionFactory extends Factory
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
            'treatment_plan_id' => null,
            'appointment_id' => null,
            'provider_id' => null,
            'session_at' => now(),
            'modality' => fake()->optional()->randomElement(['manual', 'exercise', 'electrotherapy']),
            'interventions' => fake()->optional()->paragraph(),
            'patient_response' => fake()->optional()->sentence(),
            'duration_minutes' => 45,
            'status' => ClinicalTreatmentSession::STATUS_SCHEDULED,
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
