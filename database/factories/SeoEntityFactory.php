<?php

namespace Database\Factories;

use App\Models\SeoEntity;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SeoEntity>
 */
class SeoEntityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => null,
            'tenant_key' => 'system',
            'type' => SeoEntity::TYPE_CONDITION,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(4),
            'summary' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'locale' => 'en',
            'parent_entity_id' => null,
            'status' => SeoEntity::STATUS_DRAFT,
            'author_user_id' => null,
            'reviewer_user_id' => null,
            'last_reviewed_at' => null,
            'seo_title' => null,
            'seo_description' => null,
            'canonical_path' => null,
            'schema_type' => 'MedicalCondition',
            'structured_facts' => [],
            'direct_answer' => null,
            'citations' => [],
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SeoEntity::STATUS_PUBLISHED,
            'published_at' => now(),
            'last_reviewed_at' => $attributes['last_reviewed_at'] ?? now(),
        ]);
    }

    public function reviewed(?int $reviewerUserId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'reviewer_user_id' => $reviewerUserId ?? $attributes['reviewer_user_id'],
            'last_reviewed_at' => now(),
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
