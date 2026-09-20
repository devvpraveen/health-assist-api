<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ClinicApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_create_list_show_and_update_clinic(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg('Clinic Tenant');

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/clinics', [
            'organization_id' => $organization->id,
            'name' => 'Sunrise Physio',
            'slug' => 'sunrise-physio',
            'city' => 'Bengaluru',
            'type' => 'clinic',
            'is_public' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Sunrise Physio')
            ->assertJsonPath('data.slug', 'sunrise-physio')
            ->assertJsonPath('data.city', 'Bengaluru');

        $clinicId = $create->json('data.id');

        $this->getJson('/api/v1/clinics')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $clinicId);

        $this->getJson('/api/v1/clinics/'.$clinicId)
            ->assertOk()
            ->assertJsonPath('data.uuid', $create->json('data.uuid'));

        $this->putJson('/api/v1/clinics/'.$clinicId, [
            'name' => 'Sunrise Physiotherapy',
            'city' => 'Mumbai',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Sunrise Physiotherapy')
            ->assertJsonPath('data.city', 'Mumbai');
    }

    public function test_clinic_slug_unique_per_tenant(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg('Slug Tenant');

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/clinics', [
            'organization_id' => $organization->id,
            'name' => 'Alpha Clinic',
            'slug' => 'alpha-clinic',
        ])->assertCreated();

        $this->postJson('/api/v1/clinics', [
            'organization_id' => $organization->id,
            'name' => 'Alpha Clinic 2',
            'slug' => 'alpha-clinic',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_different_tenants_can_reuse_slug(): void
    {
        [$userA, $orgA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB, $orgB] = $this->createTenantUserWithOrg('Tenant B');

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $this->postJson('/api/v1/clinics', [
            'organization_id' => $orgA->id,
            'name' => 'Shared Slug Clinic',
            'slug' => 'shared-slug',
        ])->assertCreated();

        Sanctum::actingAs($userB);
        TenantContext::set($userB->tenant_id);

        $this->postJson('/api/v1/clinics', [
            'organization_id' => $orgB->id,
            'name' => 'Shared Slug Clinic',
            'slug' => 'shared-slug',
        ])->assertCreated();
    }

    public function test_can_delete_clinic(): void
    {
        [$user, $organization, $tenant] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->deleteJson('/api/v1/clinics/'.$clinic->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('clinics', ['id' => $clinic->id]);
    }
}
