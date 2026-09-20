<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'provider_id' => fn (array $attributes) => Provider::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'branch_id' => null,
            'clinic_id' => fn (array $attributes) => Provider::query()
                ->whereKey($attributes['provider_id'])
                ->value('clinic_id'),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
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

    public function forClinic(Clinic $clinic): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $clinic->tenant_id,
            'clinic_id' => $clinic->id,
        ]);
    }
}
