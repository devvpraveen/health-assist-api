<?php

namespace Database\Factories;

use App\Models\BillingTaxRate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BillingTaxRate>
 */
class BillingTaxRateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'name' => 'GST 18%',
            'code' => 'GST18-'.Str::lower(Str::random(4)),
            'rate_bps' => 1800,
            'is_inclusive' => false,
            'is_active' => true,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'code' => 'SYS-GST18',
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
