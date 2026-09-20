<?php

namespace Database\Factories;

use App\Models\ClinicalExercisePlan;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalExercisePlan>
 */
class ClinicalExercisePlanFactory extends Factory
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
            'provider_id' => null,
            'title' => fake()->sentence(3),
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'status' => ClinicalExercisePlan::STATUS_ACTIVE,
            'notes' => fake()->optional()->paragraph(),
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
