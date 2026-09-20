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
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_slots_generated_from_schedule_and_busy_excluded(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        $monday = CarbonImmutable::now()->next(CarbonImmutable::MONDAY)->startOfDay();

        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => CarbonImmutable::MONDAY,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'slot_duration_minutes' => 30,
        ]);

        Appointment::factory()
            ->forProvider($provider)
            ->forPatient($patient)
            ->create([
                'clinic_id' => $clinic->id,
                'starts_at' => $monday->setTime(9, 30),
                'ends_at' => $monday->setTime(10, 0),
                'duration_minutes' => 30,
                'status' => Appointment::STATUS_CONFIRMED,
            ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $response = $this->getJson('/api/v1/availability?'.http_build_query([
            'provider_id' => $provider->id,
            'date' => $monday->toDateString(),
            'days' => 1,
        ]));

        $response->assertOk();

        $slots = $response->json('data');
        $starts = collect($slots)->pluck('starts_at')->map(
            fn (string $value) => CarbonImmutable::parse($value)->format('H:i')
        )->all();

        $this->assertContains('09:00', $starts);
        $this->assertContains('10:00', $starts);
        $this->assertContains('10:30', $starts);
        $this->assertNotContains('09:30', $starts);
        $this->assertCount(3, $slots);
    }
}
