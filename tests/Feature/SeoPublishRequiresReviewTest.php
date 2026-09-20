<?php

namespace Tests\Feature;

use App\Models\SeoEntity;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class SeoPublishRequiresReviewTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cannot_create_entity_as_published_directly(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/seo/entities', [
            'type' => 'condition',
            'title' => 'Neck pain',
            'summary' => 'Educational stub.',
            'body' => 'Not a diagnosis.',
            'status' => 'published',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_publish_sets_reviewer_and_makes_entity_public(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/seo/entities', [
            'type' => 'symptom',
            'title' => 'Shoulder stiffness',
            'summary' => 'Educational overview of shoulder stiffness.',
            'direct_answer' => 'Shoulder stiffness is reduced range of motion; seek care for sudden loss of motion after injury.',
            'structured_facts' => ['This is educational content only.'],
            'status' => 'draft',
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->assertNull($create->json('data.reviewer_user_id'));
        $this->assertNull($create->json('data.last_reviewed_at'));

        $this->getJson('/api/v1/public/seo/entities/symptom/shoulder-stiffness?locale=en')
            ->assertNotFound();

        $this->postJson("/api/v1/seo/entities/{$id}/publish")
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.reviewer_user_id', $user->id);

        $this->assertNotNull(SeoEntity::query()->findOrFail($id)->last_reviewed_at);

        $this->getJson('/api/v1/public/seo/entities/symptom/shoulder-stiffness?locale=en')
            ->assertOk()
            ->assertJsonPath('data.slug', 'shoulder-stiffness')
            ->assertJsonPath('data.not_a_diagnosis', true);
    }

    public function test_update_cannot_flip_to_published_without_publish_endpoint(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $entity = SeoEntity::factory()->forTenant($user->tenant)->create([
            'type' => SeoEntity::TYPE_CONDITION,
            'slug' => 'hip-pain',
            'status' => SeoEntity::STATUS_DRAFT,
        ]);

        $this->patchJson("/api/v1/seo/entities/{$entity->id}", [
            'status' => 'published',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }
}
