<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\MedicationReminder;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MedicationReminder>
 */
class MedicationReminderFactory extends Factory
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
            'schedule_id' => null,
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'channel' => MedicationReminder::CHANNEL_DATABASE,
            'scheduled_for' => now()->addHour(),
            'sent_at' => null,
            'status' => MedicationReminder::STATUS_PENDING,
            'meta' => null,
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

    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MedicationReminder::STATUS_PENDING,
            'scheduled_for' => now()->subMinute(),
            'sent_at' => null,
        ]);
    }
}
