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

class CheckInAndQueueTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_check_in_and_queue_order(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();
        $provider = Provider::factory()->forClinic($clinic)->create();
        $patientA = Patient::factory()->forTenant($user->tenant)->create(['first_name' => 'Ada']);
        $patientB = Patient::factory()->forTenant($user->tenant)->create(['first_name' => 'Bob']);

        $day = CarbonImmutable::today();

        $first = Appointment::factory()->forProvider($provider)->forPatient($patientA)->create([
            'clinic_id' => $clinic->id,
            'starts_at' => $day->setTime(9, 0),
            'ends_at' => $day->setTime(9, 30),
            'queue_number' => 1,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $second = Appointment::factory()->forProvider($provider)->forPatient($patientB)->create([
            'clinic_id' => $clinic->id,
            'starts_at' => $day->setTime(9, 30),
            'ends_at' => $day->setTime(10, 0),
            'queue_number' => 2,
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/appointments/'.$second->id.'/check-in')
            ->assertOk()
            ->assertJsonPath('data.status', Appointment::STATUS_CHECKED_IN);

        $queue = $this->getJson('/api/v1/queue?'.http_build_query([
            'clinic_id' => $clinic->id,
            'date' => $day->toDateString(),
        ]));

        $queue->assertOk();
        $ids = collect($queue->json('data'))->pluck('id')->all();

        $this->assertSame([$second->id, $first->id], $ids);
        $this->assertSame(Appointment::STATUS_CHECKED_IN, $queue->json('data.0.status'));
    }
}
