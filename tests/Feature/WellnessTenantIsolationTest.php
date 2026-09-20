<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\WellnessCategory;
use App\Models\WellnessContent;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WellnessCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class WellnessTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(WellnessCategorySeeder::class);
    }

    public function test_cannot_access_other_tenant_medications_or_tenant_wellness_content(): void
    {
        [$userA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB] = $this->createTenantUserWithOrg('Tenant B');

        $patientB = Patient::factory()->forTenant($userB->tenant)->create();
        $medicationB = Medication::factory()->forPatient($patientB)->create();

        $category = WellnessCategory::query()->where('slug', 'nutrition')->firstOrFail();
        $contentB = WellnessContent::factory()->forTenant($userB->tenant)->published()->create([
            'category_id' => $category->id,
            'title' => 'Tenant B only article',
        ]);

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $this->getJson("/api/v1/patients/{$patientB->id}/medications")
            ->assertNotFound();

        $this->getJson("/api/v1/patients/{$patientB->id}/medications/{$medicationB->id}")
            ->assertNotFound();

        $this->getJson('/api/v1/wellness/contents/'.$contentB->id)
            ->assertNotFound();

        $this->getJson('/api/v1/wellness/contents')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Tenant B only article']);
    }
}
