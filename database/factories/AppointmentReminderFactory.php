<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AppointmentReminder>
 */
class AppointmentReminderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'appointment_id' => fn (array $attributes) => Appointment::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'channel' => AppointmentReminder::CHANNEL_DATABASE,
            'scheduled_for' => now()->addHour(),
            'sent_at' => null,
            'status' => AppointmentReminder::STATUS_PENDING,
            'meta' => null,
        ];
    }

    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $appointment->tenant_id,
            'appointment_id' => $appointment->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentReminder::STATUS_PENDING,
            'sent_at' => null,
        ]);
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentReminder::STATUS_PENDING,
            'scheduled_for' => now()->subMinute(),
            'sent_at' => null,
        ]);
    }
}
