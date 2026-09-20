<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientTimelineEvent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PatientTimelineEvent>
 */
class PatientTimelineEventFactory extends Factory
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
            'event_type' => 'patient.created',
            'title' => 'Patient created',
            'description' => null,
            'subject_type' => null,
            'subject_id' => null,
            'occurred_at' => now(),
            'actor_user_id' => null,
            'meta' => null,
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
