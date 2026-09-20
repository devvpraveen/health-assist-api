<?php

namespace Database\Factories;

use App\Models\SeoEntity;
use App\Models\SeoFaq;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SeoFaq>
 */
class SeoFaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => null,
            'tenant_key' => 'system',
            'entity_id' => null,
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'locale' => 'en',
            'sort_order' => 0,
            'status' => SeoFaq::STATUS_DRAFT,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SeoFaq::STATUS_PUBLISHED,
        ]);
    }

    public function forEntity(SeoEntity $entity): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_id' => $entity->id,
            'tenant_id' => $entity->tenant_id,
            'tenant_key' => $entity->tenant_key,
            'locale' => $entity->locale,
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'tenant_key' => (string) $tenant->id,
        ]);
    }
}
