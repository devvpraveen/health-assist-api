<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Clinic';

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'organization_id' => fn (array $attributes) => Organization::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'primary_branch_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->optional()->paragraph(),
            'logo_path' => null,
            'type' => fake()->randomElement(Clinic::TYPES),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'email' => fake()->optional()->companyEmail(),
            'website' => fake()->optional()->url(),
            'whatsapp_number' => null,
            'address_line1' => fake()->optional()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'country' => fake()->optional()->countryCode(),
            'latitude' => null,
            'longitude' => null,
            'is_public' => false,
            'status' => 'active',
            'experience_years' => fake()->optional()->numberBetween(1, 40),
            'meta' => null,
            'default_locale' => 'en',
            'supported_locales' => null,
        ];
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'organization_id' => Organization::factory()->create(['tenant_id' => $tenant->id])->id,
        ]);
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $organization->tenant_id,
            'organization_id' => $organization->id,
        ]);
    }

    public function withPrimaryBranch(?Branch $branch = null): static
    {
        return $this->afterCreating(function (Clinic $clinic) use ($branch): void {
            $branch ??= Branch::factory()->create([
                'tenant_id' => $clinic->tenant_id,
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
            ]);

            $clinic->update([
                'primary_branch_id' => $branch->id,
            ]);

            if ($branch->clinic_id !== $clinic->id) {
                $branch->update(['clinic_id' => $clinic->id]);
            }
        });
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'status' => 'active',
        ]);
    }
}
