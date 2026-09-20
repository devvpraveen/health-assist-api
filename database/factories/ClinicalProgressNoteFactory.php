<?php

namespace Database\Factories;

use App\Models\ClinicalProgressNote;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalProgressNote>
 */
class ClinicalProgressNoteFactory extends Factory
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
            'treatment_plan_id' => null,
            'appointment_id' => null,
            'noted_at' => now(),
            'note' => fake()->paragraph(),
            'measurements' => ['pain_score' => fake()->numberBetween(0, 10)],
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
