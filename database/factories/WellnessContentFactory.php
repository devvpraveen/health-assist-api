<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\WellnessCategory;
use App\Models\WellnessContent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WellnessContent>
 */
class WellnessContentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => null,
            'tenant_key' => 'system',
            'category_id' => WellnessCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(4),
            'summary' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'locale' => 'en',
            'is_clinical_advice' => false,
            'status' => WellnessContent::STATUS_DRAFT,
            'author_user_id' => null,
            'published_at' => null,
            'personalization_tags' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WellnessContent::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
            'tenant_key' => (string) $tenant->id,
        ]);
    }

    public function platform(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'tenant_key' => 'system',
        ]);
    }
}
