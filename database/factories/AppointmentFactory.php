<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->setTime(10, 0);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'patient_id' => fn (array $attributes) => Patient::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'provider_id' => fn (array $attributes) => Provider::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'clinic_id' => fn (array $attributes) => Provider::query()
                ->whereKey($attributes['provider_id'])
                ->value('clinic_id'),
            'branch_id' => null,
            'service_id' => null,
            'status' => Appointment::STATUS_CONFIRMED,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'duration_minutes' => 30,
            'reason' => fake()->optional()->sentence(),
            'notes' => null,
            'booked_by_user_id' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'rescheduled_from_appointment_id' => null,
            'checked_in_at' => null,
            'queue_number' => null,
            'reminder_sent_at' => null,
            'meta' => null,
        ];
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $provider->tenant_id,
            'provider_id' => $provider->id,
            'clinic_id' => $provider->clinic_id,
        ]);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }

    public function forClinic(Clinic $clinic): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Appointment::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelled in factory',
        ]);
    }
}
