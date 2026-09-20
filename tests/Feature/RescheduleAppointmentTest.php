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

class RescheduleAppointmentTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_reschedule_marks_old_and_creates_new(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        $oldStarts = CarbonImmutable::now()->addDays(2)->setTime(9, 0);
        $newStarts = CarbonImmutable::now()->addDays(4)->setTime(14, 0);

        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => $oldStarts->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => $newStarts->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $old = Appointment::factory()
            ->forProvider($provider)
            ->forPatient($patient)
            ->create([
                'clinic_id' => $clinic->id,
                'starts_at' => $oldStarts,
                'ends_at' => $oldStarts->addMinutes(30),
                'duration_minutes' => 30,
                'queue_number' => 1,
            ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $response = $this->postJson('/api/v1/appointments/'.$old->id.'/reschedule', [
            'starts_at' => $newStarts->toIso8601String(),
            'duration_minutes' => 30,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Appointment::STATUS_CONFIRMED)
            ->assertJsonPath('data.rescheduled_from_appointment_id', $old->id);

        $this->assertDatabaseHas('appointments', [
            'id' => $old->id,
            'status' => Appointment::STATUS_RESCHEDULED,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $response->json('data.id'),
            'status' => Appointment::STATUS_CONFIRMED,
            'rescheduled_from_appointment_id' => $old->id,
        ]);

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'appointment.rescheduled',
        ]);
    }
}
