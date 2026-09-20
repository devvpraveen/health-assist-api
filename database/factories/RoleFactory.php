<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => str($name)->slug()->toString().'-'.fake()->unique()->numerify('###'),
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'tenant_key' => 'system',
        ]);
    }
}
