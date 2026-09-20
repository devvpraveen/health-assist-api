<?php

namespace Tests\Feature;

use App\Models\SeoEntity;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class SeoTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cannot_view_other_tenant_seo_entity(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB] = $this->createTenantUserWithOrg('Tenant B');

        $entityB = SeoEntity::factory()->forTenant($userB->tenant)->create([
            'title' => 'Tenant B only',
            'slug' => 'tenant-b-only',
        ]);

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $this->getJson('/api/v1/seo/entities/'.$entityB->id)
            ->assertNotFound();

        $this->getJson('/api/v1/seo/entities')
            ->assertOk()
            ->assertJsonMissing(['slug' => 'tenant-b-only']);
    }
}
