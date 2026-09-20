<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Schedule;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PublicAvailabilityTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_availability_for_public_providers(): void
    {
        [, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->public()->create();
        $provider = Provider::factory()->forClinic($clinic)->public()->create();
        $patient = Patient::factory()->forTenant($clinic->tenant)->create();

        $monday = CarbonImmutable::now()->next(CarbonImmutable::MONDAY)->startOfDay();

        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => CarbonImmutable::MONDAY,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'slot_duration_minutes' => 30,
        ]);

        Appointment::factory()->forProvider($provider)->forPatient($patient)->create([
            'clinic_id' => $clinic->id,
            'starts_at' => $monday->setTime(9, 0),
            'ends_at' => $monday->setTime(9, 30),
        ]);

        TenantContext::clear();

        $this->getJson('/api/v1/public/availability?'.http_build_query([
            'provider_uuid' => $provider->uuid,
            'date' => $monday->toDateString(),
        ]))->assertOk()
            ->assertJsonPath('data.provider_uuid', $provider->uuid)
            ->assertJsonCount(1, 'data.slots');
    }

    public function test_private_provider_not_exposed(): void
    {
        [, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create(['is_public' => false]);

        $this->getJson('/api/v1/public/availability?'.http_build_query([
            'provider_uuid' => $provider->uuid,
            'date' => now()->toDateString(),
        ]))->assertNotFound();
    }
}
