<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientTimelineEvent;
use App\Models\Provider;
use App\Models\Schedule;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class BookAppointmentTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_books_appointment_with_queue_reminder_and_timeline(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        $startsAt = CarbonImmutable::now()->addDays(3)->setTime(10, 0);

        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => $startsAt->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $response = $this->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'clinic_id' => $clinic->id,
            'starts_at' => $startsAt->toIso8601String(),
            'duration_minutes' => 30,
            'reason' => 'Follow-up visit',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Appointment::STATUS_CONFIRMED)
            ->assertJsonPath('data.queue_number', 1)
            ->assertJsonPath('data.patient_id', $patient->id);

        $appointmentId = $response->json('data.id');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'queue_number' => 1,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $this->assertTrue(
            AppointmentReminder::query()
                ->where('appointment_id', $appointmentId)
                ->where('status', AppointmentReminder::STATUS_PENDING)
                ->exists()
        );

        $this->assertDatabaseHas('patient_timeline_events', [
            'patient_id' => $patient->id,
            'event_type' => 'appointment.booked',
        ]);

        $this->assertInstanceOf(
            PatientTimelineEvent::class,
            PatientTimelineEvent::query()->where('event_type', 'appointment.booked')->first()
        );
    }

    public function test_overlap_is_rejected(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();
        $otherPatient = Patient::factory()->forTenant($user->tenant)->create();

        $startsAt = CarbonImmutable::now()->addDays(2)->setTime(11, 0);

        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => $startsAt->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
        ]);

        Appointment::factory()
            ->forProvider($provider)
            ->forPatient($patient)
            ->create([
                'clinic_id' => $clinic->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes(30),
                'duration_minutes' => 30,
            ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/appointments', [
            'patient_id' => $otherPatient->id,
            'provider_id' => $provider->id,
            'clinic_id' => $clinic->id,
            'starts_at' => $startsAt->toIso8601String(),
            'duration_minutes' => 30,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    }

    public function test_booking_outside_schedule_is_rejected(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        $startsAt = CarbonImmutable::now()->addDays(2)->setTime(20, 0);

        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => $startsAt->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/appointments', [
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
            'clinic_id' => $clinic->id,
            'starts_at' => $startsAt->toIso8601String(),
            'duration_minutes' => 30,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['starts_at']);
    }
}
