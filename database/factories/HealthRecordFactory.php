<?php

namespace Database\Factories;

use App\Models\HealthRecord;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HealthRecord>
 */
class HealthRecordFactory extends Factory
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
            'category' => fake()->randomElement(HealthRecord::CATEGORIES),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'recorded_at' => fake()->optional()->dateTimeBetween('-2 years'),
            'status' => 'active',
            'integrity_hash' => null,
            'integrity_status' => 'disabled',
            'integrity_provider' => null,
            'integrity_proof_ref' => null,
            'integrity_attested_at' => null,
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
