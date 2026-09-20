<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MedicationLog>
 */
class MedicationLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'medication_id' => fn (array $attributes) => Medication::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'schedule_id' => null,
            'scheduled_for' => null,
            'logged_at' => now(),
            'status' => MedicationLog::STATUS_TAKEN,
            'notes' => null,
            'logged_by_user_id' => null,
            'idempotency_key' => null,
        ];
    }

    public function forMedication(Medication $medication): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $medication->tenant_id,
            'medication_id' => $medication->id,
            'patient_id' => $medication->patient_id,
        ]);
    }
}
