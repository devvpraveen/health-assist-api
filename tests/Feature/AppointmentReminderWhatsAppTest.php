<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Notifications\AppointmentReminderNotification;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AppointmentReminderWhatsAppTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_reminder_also_sends_whatsapp_when_account_and_phone_configured(): void
    {
        Notification::fake();
        Http::fake([
            'evolution.test/*' => Http::response(['key' => ['id' => 'reminder-1']], 200),
        ]);

        [$user, $organization] = $this->createTenantUserWithOrg();
        $patientUser = User::factory()->forTenant($user->tenant)->create();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->forUser($patientUser)->create([
            'phone' => '5511987654321',
        ]);

        TenantContext::set($user->tenant_id);

        WhatsAppAccount::factory()->forTenant($user->tenant)->create([
            'instance_name' => 'reminder-inst',
            'is_active' => true,
        ]);

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

        Notification::assertSentTo($patientUser, AppointmentReminderNotification::class);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/message/sendText/reminder-inst')
                && $request['number'] === '5511987654321'
                && str_contains((string) $request['text'], 'appointment');
        });

        $this->assertTrue((bool) data_get($reminder->fresh()->meta, 'whatsapp_sent'));
    }
}
