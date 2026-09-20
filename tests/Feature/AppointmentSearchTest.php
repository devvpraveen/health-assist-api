<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AppointmentSearchTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_filters_appointments(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $otherClinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $otherProvider = Provider::factory()->forClinic($otherClinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create(['first_name' => 'Zara']);
        $otherPatient = Patient::factory()->forTenant($user->tenant)->create(['first_name' => 'Milo']);

        $match = Appointment::factory()->forProvider($provider)->forPatient($patient)->create([
            'clinic_id' => $clinic->id,
            'status' => Appointment::STATUS_CONFIRMED,
            'starts_at' => CarbonImmutable::parse('2026-10-01 10:00:00'),
            'ends_at' => CarbonImmutable::parse('2026-10-01 10:30:00'),
            'reason' => 'Knee review',
        ]);

        Appointment::factory()->forProvider($otherProvider)->forPatient($otherPatient)->create([
            'clinic_id' => $otherClinic->id,
            'status' => Appointment::STATUS_CANCELLED,
            'starts_at' => CarbonImmutable::parse('2026-10-02 10:00:00'),
            'ends_at' => CarbonImmutable::parse('2026-10-02 10:30:00'),
            'reason' => 'Dental',
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->getJson('/api/v1/appointments?'.http_build_query([
            'status' => Appointment::STATUS_CONFIRMED,
            'provider_id' => $provider->id,
            'clinic_id' => $clinic->id,
            'patient_id' => $patient->id,
            'date_from' => '2026-10-01 00:00:00',
            'date_to' => '2026-10-01 23:59:59',
            'q' => 'Knee',
        ]))->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);

        $this->getJson('/api/v1/appointments?q=Zara')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $match->id);
    }
}
