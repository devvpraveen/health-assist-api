<?php

namespace Database\Factories;

use App\Models\ClinicalTreatmentPlan;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalTreatmentPlan>
 */
class ClinicalTreatmentPlanFactory extends Factory
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
            'clinic_id' => null,
            'title' => fake()->sentence(3),
            'diagnosis_summary' => fake()->optional()->sentence(),
            'goals' => ['Improve ROM', 'Reduce pain'],
            'frequency' => '2x/week',
            'duration_weeks' => 6,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'reassessment_date' => null,
            'home_program_notes' => fake()->optional()->paragraph(),
            'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
            'source' => ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
            'authored_by_user_id' => fn (array $attributes) => User::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'approved_by_user_id' => null,
            'approved_at' => null,
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
