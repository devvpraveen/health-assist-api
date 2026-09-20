<?php

namespace Tests\Feature;

use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\MedicationReminder;
use App\Models\MedicationSchedule;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\MedicationMissedDoseNotification;
use App\Notifications\MedicationReminderNotification;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class MedicationReminderCommandTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_command_creates_and_sends_due_reminders(): void
    {
        Notification::fake();

        [$user] = $this->createTenantUserWithOrg();
        $patientUser = User::factory()->forTenant($user->tenant)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->forUser($patientUser)->create();

        TenantContext::set($user->tenant_id);

        $now = CarbonImmutable::parse('2026-09-11 08:00:00', 'UTC');
        $this->travelTo($now);

        $medication = Medication::factory()->forPatient($patient)->create([
            'start_date' => $now->toDateString(),
            'status' => Medication::STATUS_ACTIVE,
        ]);

        MedicationSchedule::factory()->forMedication($medication)->create([
            'time_of_day' => '08:05:00',
            'timezone' => 'UTC',
            'days_of_week' => null,
            'is_active' => true,
        ]);

        config(['medication.reminder_lookahead_minutes' => 30]);

        $this->artisan('medications:send-reminders', ['--sync' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('medication_reminders', [
            'medication_id' => $medication->id,
            'status' => MedicationReminder::STATUS_PENDING,
        ]);

        $this->travelTo($now->addMinutes(6));

        $this->artisan('medications:send-reminders', ['--sync' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('medication_reminders', [
            'medication_id' => $medication->id,
            'status' => MedicationReminder::STATUS_SENT,
        ]);

        Notification::assertSentTo($patientUser, MedicationReminderNotification::class);

        TenantContext::clear();
    }

    public function test_command_marks_missed_after_grace(): void
    {
        Notification::fake();

        [$user] = $this->createTenantUserWithOrg();
        $patientUser = User::factory()->forTenant($user->tenant)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->forUser($patientUser)->create();

        TenantContext::set($user->tenant_id);

        $scheduledFor = CarbonImmutable::parse('2026-09-11 07:00:00', 'UTC');
        $this->travelTo($scheduledFor->addMinutes(90));

        $medication = Medication::factory()->forPatient($patient)->create([
            'status' => Medication::STATUS_ACTIVE,
            'start_date' => $scheduledFor->toDateString(),
        ]);

        MedicationReminder::factory()->forMedication($medication)->create([
            'scheduled_for' => $scheduledFor,
            'status' => MedicationReminder::STATUS_SENT,
            'sent_at' => $scheduledFor,
        ]);

        config(['medication.missed_grace_minutes' => 60]);

        $this->artisan('medications:send-reminders', ['--sync' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('medication_logs', [
            'medication_id' => $medication->id,
            'status' => MedicationLog::STATUS_MISSED,
        ]);

        Notification::assertSentTo($patientUser, MedicationMissedDoseNotification::class);

        TenantContext::clear();
    }
}
