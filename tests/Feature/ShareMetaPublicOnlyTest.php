<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\SeoEntity;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ShareMetaPublicOnlyTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_share_meta_returns_published_seo_entity_only(): void
    {
        SeoEntity::factory()->platform()->create([
            'type' => SeoEntity::TYPE_CONDITION,
            'slug' => 'shareable-back-pain',
            'title' => 'Shareable Back Pain',
            'seo_title' => 'Back Pain Guide',
            'seo_description' => 'Public educational content',
            'status' => SeoEntity::STATUS_PUBLISHED,
            'published_at' => now(),
            'last_reviewed_at' => now(),
            'locale' => 'en',
        ]);

        SeoEntity::factory()->platform()->create([
            'type' => SeoEntity::TYPE_CONDITION,
            'slug' => 'draft-private',
            'title' => 'Draft',
            'status' => SeoEntity::STATUS_DRAFT,
            'locale' => 'en',
        ]);

        $this->getJson('/api/v1/public/share-meta?type=seo_entity&entity_type=condition&slug=shareable-back-pain&locale=en')
            ->assertOk()
            ->assertJsonPath('data.title', 'Back Pain Guide')
            ->assertJsonPath('data.type', 'seo_entity');

        $this->getJson('/api/v1/public/share-meta?type=seo_entity&entity_type=condition&slug=draft-private&locale=en')
            ->assertNotFound();
    }

    public function test_share_meta_returns_public_clinic_only(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();

        $public = Clinic::factory()->create([
            'tenant_id' => $user->tenant_id,
            'organization_id' => $organization->id,
            'name' => 'Public Clinic',
            'slug' => 'public-clinic',
            'is_public' => true,
            'status' => 'active',
            'description' => 'A public clinic',
        ]);

        Clinic::factory()->create([
            'tenant_id' => $user->tenant_id,
            'organization_id' => $organization->id,
            'name' => 'Private Clinic',
            'slug' => 'private-clinic',
            'is_public' => false,
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/public/share-meta?type=clinic&slug='.$public->slug)
            ->assertOk()
            ->assertJsonPath('data.type', 'clinic');

        $this->getJson('/api/v1/public/share-meta?type=clinic&slug=private-clinic')
            ->assertNotFound();
    }
}
