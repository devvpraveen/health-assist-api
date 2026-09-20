<?php

namespace Tests\Feature;

use App\Models\AttributionTouch;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AttributionCaptureTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_attribution_capture_persists_touch(): void
    {
        $this->postJson('/api/v1/public/attribution', [
            'anonymous_id' => 'anon-attr-1',
            'campaign' => 'spring',
            'source' => 'google',
            'medium' => 'cpc',
            'content' => 'ad1',
            'term' => 'physio',
            'referrer' => 'https://example.com',
            'landing_path' => '/en/conditions/back-pain',
        ])->assertCreated()
            ->assertJsonPath('data.anonymous_id', 'anon-attr-1')
            ->assertJsonPath('data.source', 'google');

        $this->assertDatabaseHas('attribution_touches', [
            'anonymous_id' => 'anon-attr-1',
            'campaign' => 'spring',
            'medium' => 'cpc',
        ]);
    }

    public function test_staff_can_list_attribution_touches(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        AttributionTouch::factory()->create([
            'tenant_id' => $user->tenant_id,
            'anonymous_id' => 'anon-listed',
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->getJson('/api/v1/attribution/touches')
            ->assertOk()
            ->assertJsonFragment(['anonymous_id' => 'anon-listed']);
    }
}
