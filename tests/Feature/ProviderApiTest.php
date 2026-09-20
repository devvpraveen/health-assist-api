<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\Specialty;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ProviderApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SpecialtySeeder::class);
    }

    public function test_can_create_list_show_and_update_provider(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/providers', [
            'clinic_id' => $clinic->id,
            'first_name' => 'Anil',
            'last_name' => 'Mehta',
            'type' => 'doctor',
            'display_name' => 'Dr. Anil Mehta',
            'is_public' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.first_name', 'Anil')
            ->assertJsonPath('data.type', 'doctor');

        $providerId = $create->json('data.id');

        $this->getJson('/api/v1/providers')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/providers/'.$providerId)
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Dr. Anil Mehta');

        $this->putJson('/api/v1/providers/'.$providerId, [
            'bio' => 'Orthopedic specialist',
            'years_experience' => 12,
        ])
            ->assertOk()
            ->assertJsonPath('data.bio', 'Orthopedic specialist')
            ->assertJsonPath('data.years_experience', 12);
    }

    public function test_can_sync_provider_specialties_and_branches(): void
    {
        [$user, $organization, $tenant] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $branch = Branch::factory()->forOrganization($organization)->create([
            'clinic_id' => $clinic->id,
        ]);
        $provider = Provider::factory()->forClinic($clinic)->create();
        $specialty = Specialty::query()->where('slug', 'physiotherapy')->firstOrFail();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->putJson('/api/v1/providers/'.$provider->id.'/specialties', [
            'specialty_ids' => [$specialty->id],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.specialties')
            ->assertJsonPath('data.specialties.0.slug', 'physiotherapy');

        $this->putJson('/api/v1/providers/'.$provider->id.'/branches', [
            'branch_ids' => [$branch->id],
        ])
            ->assertOk()
            ->assertJsonCount(1, 'data.branches')
            ->assertJsonPath('data.branches.0.id', $branch->id);

        $this->assertDatabaseHas('provider_specialty', [
            'provider_id' => $provider->id,
            'specialty_id' => $specialty->id,
        ]);

        $this->assertDatabaseHas('provider_branch', [
            'provider_id' => $provider->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_can_delete_provider(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->deleteJson('/api/v1/providers/'.$provider->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('providers', ['id' => $provider->id]);
    }
}
