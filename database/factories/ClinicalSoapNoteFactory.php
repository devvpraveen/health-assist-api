<?php

namespace Database\Factories;

use App\Models\ClinicalSoapNote;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ClinicalSoapNote>
 */
class ClinicalSoapNoteFactory extends Factory
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
            'provider_id' => fn (array $attributes) => Provider::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'clinic_id' => null,
            'appointment_id' => null,
            'assessment_id' => null,
            'subjective' => fake()->optional()->paragraph(),
            'objective' => fake()->optional()->paragraph(),
            'assessment' => fake()->optional()->paragraph(),
            'plan' => fake()->optional()->paragraph(),
            'session_date' => now(),
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
            'provider_id' => Provider::factory()->create(['tenant_id' => $patient->tenant_id])->id,
        ]);
    }
}
