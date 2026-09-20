<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Notifications\AppointmentReminderNotification;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class SendAppointmentRemindersTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_command_marks_due_reminder_sent(): void
    {
        Notification::fake();

        [$user, $organization] = $this->createTenantUserWithOrg();
        $patientUser = User::factory()->forTenant($user->tenant)->create();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->forUser($patientUser)->create();

        TenantContext::set($user->tenant_id);

        $appointment = Appointment::factory()
            ->forProvider($provider)
            ->forPatient($patient)
            ->create([
                'clinic_id' => $clinic->id,
                'starts_at' => CarbonImmutable::now()->addHours(3),
                'ends_at' => CarbonImmutable::now()->addHours(3)->addMinutes(30),
            ]);

        $reminder = AppointmentReminder::factory()->forAppointment($appointment)->due()->create([
            'channel' => AppointmentReminder::CHANNEL_DATABASE,
        ]);

        TenantContext::clear();

        $this->artisan('appointments:send-reminders', ['--sync' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('appointment_reminders', [
            'id' => $reminder->id,
            'status' => AppointmentReminder::STATUS_SENT,
        ]);

        $this->assertNotNull($appointment->fresh()->reminder_sent_at);

        Notification::assertSentTo($patientUser, AppointmentReminderNotification::class);
    }
}
