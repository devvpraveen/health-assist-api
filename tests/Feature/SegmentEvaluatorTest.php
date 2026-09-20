<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AudienceSegment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Services\Marketing\SegmentEvaluator;
use App\Support\TenantContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class SegmentEvaluatorTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_new_user_and_inactive_rules_return_user_ids_only(): void
    {
        [$admin, $organization, $tenant] = $this->createTenantUserWithOrg();

        $newUser = User::factory()->forTenant($tenant)->create([
            'created_at' => now()->subDays(2),
            'last_login_at' => now(),
        ]);

        $inactive = User::factory()->forTenant($tenant)->create([
            'created_at' => now()->subDays(60),
            'last_login_at' => now()->subDays(40),
        ]);

        $segment = AudienceSegment::factory()->create([
            'tenant_id' => $tenant->id,
            'key' => 'inactive_30',
            'definition' => ['inactive_days' => 30],
        ]);

        $evaluator = app(SegmentEvaluator::class);
        $ids = $evaluator->memberIds($segment);

        $this->assertContains($inactive->id, $ids);
        $this->assertNotContains($newUser->id, $ids);

        $upcomingSegment = AudienceSegment::factory()->create([
            'tenant_id' => $tenant->id,
            'key' => 'upcoming_appt',
            'definition' => ['appointment_upcoming' => true],
        ]);

        $patientUser = User::factory()->forTenant($tenant)->create();
        $patient = Patient::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $patientUser->id,
        ]);

        $clinic = Clinic::factory()->create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
        ]);
        $provider = Provider::factory()->create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $clinic->id,
        ]);

        Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'clinic_id' => $clinic->id,
            'provider_id' => $provider->id,
            'patient_id' => $patient->id,
            'starts_at' => now()->addDays(3),
            'ends_at' => now()->addDays(3)->addMinutes(30),
            'status' => Appointment::STATUS_CONFIRMED,
        ]);

        $upcomingIds = $evaluator->memberIds($upcomingSegment);
        $this->assertSame([$patientUser->id], $upcomingIds);

        Sanctum::actingAs($admin);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/segments/upcoming_appt/members')
            ->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.user_ids', [$patientUser->id]);
    }
}
