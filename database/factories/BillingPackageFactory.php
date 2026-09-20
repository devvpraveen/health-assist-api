<?php

namespace Database\Factories;

use App\Models\BillingPackage;
use App\Models\Clinic;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BillingPackage>
 */
class BillingPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'clinic_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->optional()->sentence(),
            'session_count' => 10,
            'validity_days' => 90,
            'price_cents' => 500000,
            'currency' => 'INR',
            'is_active' => true,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
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
