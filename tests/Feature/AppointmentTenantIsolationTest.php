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

class AppointmentTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cannot_view_other_tenant_appointments(): void
    {
        [$userA, $organizationA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB, $organizationB] = $this->createTenantUserWithOrg('Tenant B');

        $clinicB = Clinic::factory()->forOrganization($organizationB)->create();
        $providerB = Provider::factory()->forClinic($clinicB)->create();
        $patientB = Patient::factory()->forTenant($userB->tenant)->create();

        $appointmentB = Appointment::factory()
            ->forProvider($providerB)
            ->forPatient($patientB)
            ->create([
                'clinic_id' => $clinicB->id,
                'starts_at' => CarbonImmutable::now()->addDay()->setTime(10, 0),
                'ends_at' => CarbonImmutable::now()->addDay()->setTime(10, 30),
            ]);

        Sanctum::actingAs($userA);
        TenantContext::set($userA->tenant_id);

        $this->getJson('/api/v1/appointments/'.$appointmentB->id)
            ->assertNotFound();

        $this->getJson('/api/v1/appointments')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $clinicA = Clinic::factory()->forOrganization($organizationA)->create();
        $providerA = Provider::factory()->forClinic($clinicA)->create();
        $patientA = Patient::factory()->forTenant($userA->tenant)->create();

        $this->postJson('/api/v1/appointments', [
            'patient_id' => $patientB->id,
            'provider_id' => $providerA->id,
            'clinic_id' => $clinicA->id,
            'starts_at' => CarbonImmutable::now()->addDays(2)->setTime(11, 0)->toIso8601String(),
            'duration_minutes' => 30,
        ])->assertUnprocessable();
    }
}
