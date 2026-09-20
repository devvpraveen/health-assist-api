<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'organization_id' => fn (array $attributes) => Organization::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'name' => fake()->city().' Branch',
            'code' => strtoupper(fake()->unique()->bothify('BR-###')),
            'timezone' => 'UTC',
            'status' => 'active',
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $organization->tenant_id,
            'organization_id' => $organization->id,
        ]);
    }
}
