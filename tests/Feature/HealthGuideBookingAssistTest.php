<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Database\Seeders\SpecialtySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class HealthGuideBookingAssistTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SpecialtySeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
    }

    public function test_confirm_true_books_via_existing_appointments(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();
        $physio = Specialty::query()->where('slug', 'physiotherapy')->firstOrFail();

        $provider = Provider::factory()->forClinic($clinic)->create([
            'type' => 'physiotherapist',
            'verification_status' => 'verified',
            'is_public' => true,
            'status' => 'active',
        ]);
        $provider->specialties()->sync([$physio->id => ['is_primary' => true]]);

        $startsAt = CarbonImmutable::now()->addDays(4)->setTime(10, 0);
        Schedule::factory()->forProvider($provider)->create([
            'day_of_week' => $startsAt->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $uuid = $this->postJson('/api/v1/health-guide/conversations', [
            'patient_id' => $patient->id,
        ])->assertCreated()->json('data.uuid');

        $this->postJson("/api/v1/health-guide/conversations/{$uuid}/messages", [
            'content' => "I've had mild knee pain for two weeks",
        ])->assertOk();

        $response = $this->postJson("/api/v1/health-guide/conversations/{$uuid}/book", [
            'provider_id' => $provider->id,
            'clinic_id' => $clinic->id,
            'starts_at' => $startsAt->toIso8601String(),
            'confirm' => true,
            'reason' => 'Knee physio consult',
        ])->assertCreated()
            ->assertJsonPath('data.appointment.status', Appointment::STATUS_CONFIRMED)
            ->assertJsonPath('data.appointment.provider_id', $provider->id);

        $this->assertDatabaseHas('appointments', [
            'id' => $response->json('data.appointment.id'),
            'patient_id' => $patient->id,
            'provider_id' => $provider->id,
        ]);
    }

    public function test_booking_without_confirm_is_rejected(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $patient = Patient::factory()->forTenant($user->tenant)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $uuid = $this->postJson('/api/v1/health-guide/conversations', [
            'patient_id' => $patient->id,
        ])->assertCreated()->json('data.uuid');

        $this->postJson("/api/v1/health-guide/conversations/{$uuid}/book", [
            'provider_id' => $provider->id,
            'starts_at' => now()->addDay()->toIso8601String(),
            'confirm' => false,
        ])->assertStatus(422);
    }
}
