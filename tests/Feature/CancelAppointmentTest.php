<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
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

class CancelAppointmentTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_cancels_appointment_and_pending_reminders(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        $appointment = Appointment::factory()
            ->forProvider($provider)
            ->forPatient($patient)
            ->create([
                'clinic_id' => $clinic->id,
                'starts_at' => CarbonImmutable::now()->addDays(2)->setTime(10, 0),
                'ends_at' => CarbonImmutable::now()->addDays(2)->setTime(10, 30),
            ]);

        $reminder = AppointmentReminder::factory()->forAppointment($appointment)->pending()->create([
            'scheduled_for' => CarbonImmutable::now()->addDay(),
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/appointments/'.$appointment->id.'/cancel', [
            'cancellation_reason' => 'Patient unavailable',
        ])->assertOk()
            ->assertJsonPath('data.status', Appointment::STATUS_CANCELLED);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => Appointment::STATUS_CANCELLED,
            'cancellation_reason' => 'Patient unavailable',
        ]);

        $this->assertDatabaseHas('appointment_reminders', [
            'id' => $reminder->id,
            'status' => AppointmentReminder::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'appointment.cancelled',
        ]);
    }
}
