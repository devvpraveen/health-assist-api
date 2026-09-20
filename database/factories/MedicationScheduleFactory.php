<?php

namespace Database\Factories;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MedicationSchedule>
 */
class MedicationScheduleFactory extends Factory
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
            'time_of_day' => '08:00:00',
            'timezone' => 'UTC',
            'days_of_week' => null,
            'is_active' => true,
        ];
    }

    public function forMedication(Medication $medication): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $medication->tenant_id,
            'medication_id' => $medication->id,
        ]);
    }
}
