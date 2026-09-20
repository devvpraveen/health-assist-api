<?php

namespace Tests\Feature;

use App\Models\SeoEntity;
use App\Models\SeoFaq;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SeoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoEntitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SeoContentSeeder::class);
    }

    public function test_public_lists_only_published_reviewed_entities(): void
    {
        SeoEntity::factory()->platform()->create([
            'type' => SeoEntity::TYPE_CONDITION,
            'slug' => 'draft-condition',
            'title' => 'Draft condition',
            'status' => SeoEntity::STATUS_DRAFT,
            'locale' => 'en',
        ]);

        SeoEntity::factory()->platform()->create([
            'type' => SeoEntity::TYPE_CONDITION,
            'slug' => 'published-unreviewed',
            'title' => 'Published without review',
            'status' => SeoEntity::STATUS_PUBLISHED,
            'published_at' => now(),
            'last_reviewed_at' => null,
            'locale' => 'en',
        ]);

        $response = $this->getJson('/api/v1/public/seo/entities?type=condition&locale=en')
            ->assertOk();

        $slugs = collect($response->json('data'))->pluck('slug');

        $this->assertTrue($slugs->contains('back-pain'));
        $this->assertFalse($slugs->contains('draft-condition'));
        $this->assertFalse($slugs->contains('published-unreviewed'));
    }

    public function test_public_entity_show_returns_aeo_fields_and_disclaimer(): void
    {
        $this->getJson('/api/v1/public/seo/entities/condition/back-pain?locale=en')
            ->assertOk()
            ->assertJsonPath('data.slug', 'back-pain')
            ->assertJsonPath('data.not_a_diagnosis', true)
            ->assertJsonPath('data.citations', [])
            ->assertJsonStructure([
                'data' => [
                    'direct_answer',
                    'structured_facts',
                    'disclaimer',
                    'faqs',
                    'related',
                ],
            ]);
    }

    public function test_public_faqs_and_wellness_endpoints(): void
    {
        $this->getJson('/api/v1/public/seo/faqs?locale=en')
            ->assertOk()
            ->assertJsonFragment(['question' => 'Is Health Assist content a medical diagnosis?']);

        $this->getJson('/api/v1/public/seo/faqs?locale=en&entity=back-pain')
            ->assertOk()
            ->assertJsonFragment(['question' => 'Is back pain always serious?']);

        $this->getJson('/api/v1/public/wellness/contents?locale=en')
            ->assertOk();
    }

    public function test_hreflang_locale_stub_exists_for_hindi(): void
    {
        $this->getJson('/api/v1/public/seo/entities/condition/back-pain?locale=hi')
            ->assertOk()
            ->assertJsonPath('data.locale', 'hi')
            ->assertJsonPath('data.slug', 'back-pain');
    }
}
