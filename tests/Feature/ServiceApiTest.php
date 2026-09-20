<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Service;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_crud_services_under_clinic(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/clinics/'.$clinic->id.'/services', [
            'name' => 'Initial Assessment',
            'slug' => 'initial-assessment',
            'duration_minutes' => 45,
            'price_cents' => 150000,
            'currency' => 'INR',
            'is_public' => true,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.name', 'Initial Assessment')
            ->assertJsonPath('data.duration_minutes', 45)
            ->assertJsonPath('data.clinic_id', $clinic->id);

        $serviceId = $create->json('data.id');

        $this->getJson('/api/v1/clinics/'.$clinic->id.'/services')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/clinics/'.$clinic->id.'/services/'.$serviceId)
            ->assertOk()
            ->assertJsonPath('data.slug', 'initial-assessment');

        $this->putJson('/api/v1/clinics/'.$clinic->id.'/services/'.$serviceId, [
            'price_cents' => 175000,
            'duration_minutes' => 60,
        ])
            ->assertOk()
            ->assertJsonPath('data.price_cents', 175000)
            ->assertJsonPath('data.duration_minutes', 60);

        $this->deleteJson('/api/v1/clinics/'.$clinic->id.'/services/'.$serviceId)
            ->assertNoContent();

        $this->assertDatabaseMissing('services', ['id' => $serviceId]);
    }

    public function test_service_slug_unique_per_clinic(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        Service::factory()->forClinic($clinic)->create(['slug' => 'follow-up']);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/clinics/'.$clinic->id.'/services', [
            'name' => 'Follow Up Duplicate',
            'slug' => 'follow-up',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }
}
