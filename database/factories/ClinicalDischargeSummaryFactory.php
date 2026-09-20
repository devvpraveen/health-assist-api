<?php

namespace Database\Factories;

use App\Models\ClinicalDischargeSummary;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalDischargeSummary>
 */
class ClinicalDischargeSummaryFactory extends Factory
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
            'treatment_plan_id' => null,
            'discharged_at' => now(),
            'reason' => fake()->optional()->sentence(),
            'initial_condition' => fake()->optional()->paragraph(),
            'treatment_provided' => fake()->optional()->paragraph(),
            'progress_summary' => fake()->optional()->paragraph(),
            'current_status' => fake()->optional()->sentence(),
            'home_program' => fake()->optional()->paragraph(),
            'follow_up' => fake()->optional()->sentence(),
            'referral' => null,
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
