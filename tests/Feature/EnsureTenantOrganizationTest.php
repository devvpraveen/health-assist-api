<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\ModulePlatformSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnsureTenantOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModulePlatformSeeder::class);
    }

    public function test_clinic_persona_creates_organization_and_index_ensures_it(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Fresh Clinic Tenant']);
        $user = User::factory()->forTenant($tenant)->create(['name' => 'Clinic Admin']);

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->postJson('/api/v1/auth/persona', ['persona' => 'clinic'])
            ->assertOk();

        $this->assertDatabaseHas('organizations', [
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($user->fresh()->hasRole('clinic_admin'));

        $list = $this->getJson('/api/v1/organizations')->assertOk();
        $this->assertGreaterThanOrEqual(1, count($list->json('data')));
        $this->assertInstanceOf(Organization::class, Organization::query()->where('tenant_id', $tenant->id)->first());
    }

    public function test_provider_cannot_list_organizations(): void
    {
        $tenant = Tenant::factory()->create();
        Organization::factory()->create(['tenant_id' => $tenant->id]);
        $doctor = User::factory()->forTenant($tenant)->create();
        $role = Role::query()->where('slug', 'provider')->whereNull('tenant_id')->firstOrFail();
        $doctor->roles()->attach($role->id, [
            'tenant_id' => $tenant->id,
            'branch_id' => null,
            'branch_key' => 'none',
        ]);

        Sanctum::actingAs($doctor);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/organizations')->assertForbidden();
    }
}
