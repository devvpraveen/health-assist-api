<?php

namespace Database\Factories;

use App\Models\AppointmentWaitlistEntry;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AppointmentWaitlistEntry>
 */
class AppointmentWaitlistEntryFactory extends Factory
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
            'clinic_id' => fn (array $attributes) => Clinic::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'service_id' => null,
            'preferred_date' => fake()->optional()->dateTimeBetween('now', '+2 weeks')?->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'status' => AppointmentWaitlistEntry::STATUS_PENDING,
        ];
    }

    public function forClinic(Clinic $clinic): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
        ]);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $patient->tenant_id,
            'patient_id' => $patient->id,
        ]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $provider->tenant_id,
            'provider_id' => $provider->id,
            'clinic_id' => $provider->clinic_id,
        ]);
    }
}
