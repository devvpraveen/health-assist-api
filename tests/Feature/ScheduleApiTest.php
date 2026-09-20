<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Provider;
use App\Models\Schedule;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ScheduleApiTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_can_crud_provider_schedules(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $create = $this->postJson('/api/v1/providers/'.$provider->id.'/schedules', [
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'slot_duration_minutes' => 30,
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.day_of_week', 1)
            ->assertJsonPath('data.start_time', '09:00')
            ->assertJsonPath('data.end_time', '13:00')
            ->assertJsonPath('data.clinic_id', $clinic->id);

        $scheduleId = $create->json('data.id');

        $this->getJson('/api/v1/providers/'.$provider->id.'/schedules')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/v1/providers/'.$provider->id.'/schedules/'.$scheduleId, [
            'end_time' => '17:00',
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.end_time', '17:00')
            ->assertJsonPath('data.is_active', false);

        $this->deleteJson('/api/v1/providers/'.$provider->id.'/schedules/'.$scheduleId)
            ->assertNoContent();

        $this->assertDatabaseMissing('schedules', ['id' => $scheduleId]);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/providers/'.$provider->id.'/schedules', [
            'day_of_week' => 2,
            'start_time' => '14:00',
            'end_time' => '10:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }

    public function test_update_rejects_invalid_time_range(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $schedule = Schedule::factory()->forProvider($provider)->create([
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->putJson('/api/v1/providers/'.$provider->id.'/schedules/'.$schedule->id, [
            'start_time' => '15:00',
            'end_time' => '11:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['end_time']);
    }
}
