<?php

namespace Tests\Feature;

use App\Models\Specialty;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class SpecialtyApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SpecialtySeeder::class);
    }

    public function test_lists_system_specialties_and_can_create_tenant_specialty(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $list = $this->getJson('/api/v1/specialties');

        $list->assertOk();
        $this->assertGreaterThanOrEqual(6, count($list->json('data')));

        $slugs = collect($list->json('data'))->pluck('slug')->all();
        $this->assertContains('physiotherapy', $slugs);
        $this->assertContains('orthopedics', $slugs);

        $create = $this->postJson('/api/v1/specialties', [
            'name' => 'Sports Rehab',
            'slug' => 'sports-rehab',
            'description' => 'Tenant custom specialty',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.slug', 'sports-rehab')
            ->assertJsonPath('data.is_system', false)
            ->assertJsonPath('data.tenant_id', $user->tenant_id);

        $this->assertDatabaseHas('specialties', [
            'slug' => 'sports-rehab',
            'tenant_id' => $user->tenant_id,
            'tenant_key' => (string) $user->tenant_id,
        ]);
    }

    public function test_cannot_update_or_delete_system_specialty(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $system = Specialty::query()->where('slug', 'dentistry')->whereNull('tenant_id')->firstOrFail();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->putJson('/api/v1/specialties/'.$system->id, [
            'name' => 'Hacked Dentistry',
        ])->assertForbidden();

        $this->deleteJson('/api/v1/specialties/'.$system->id)
            ->assertForbidden();
    }
}
