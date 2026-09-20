<?php

namespace Database\Factories;

use App\Models\ClinicalAssessment;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalAssessment>
 */
class ClinicalAssessmentFactory extends Factory
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
            'appointment_id' => null,
            'template_key' => fake()->optional()->randomElement(['general', 'mobility', 'gait', 'functional']),
            'assessed_at' => now(),
            'chief_complaint' => fake()->optional()->sentence(),
            'findings' => ['pain_score' => fake()->numberBetween(0, 10)],
            'summary' => fake()->optional()->paragraph(),
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
