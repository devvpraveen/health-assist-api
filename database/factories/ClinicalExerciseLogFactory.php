<?php

namespace Database\Factories;

use App\Models\ClinicalExerciseLog;
use App\Models\ClinicalExercisePlanItem;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalExerciseLog>
 */
class ClinicalExerciseLogFactory extends Factory
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
            'exercise_plan_item_id' => ClinicalExercisePlanItem::factory(),
            'performed_at' => now(),
            'result' => ClinicalExerciseLog::RESULT_COMPLETED,
            'notes' => fake()->optional()->sentence(),
            'pain_score' => fake()->optional()->numberBetween(0, 10),
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
