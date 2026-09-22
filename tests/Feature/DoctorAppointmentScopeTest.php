<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Role;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class DoctorAppointmentScopeTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_doctor_only_lists_own_provider_appointments(): void
    {
        [$admin, $organization, $tenant] = $this->createTenantUserWithOrg();
        $clinic = Clinic::factory()->forOrganization($organization)->create();

        $otherProvider = Provider::factory()->forClinic($clinic)->create([
            'display_name' => 'Other Doctor',
        ]);
        $patient = Patient::factory()->forTenant($tenant)->create();
        Appointment::factory()
            ->forProvider($otherProvider)
            ->forPatient($patient)
            ->create([
                'clinic_id' => $clinic->id,
                'starts_at' => CarbonImmutable::now()->setTime(10, 0),
                'ends_at' => CarbonImmutable::now()->setTime(10, 30),
                'status' => 'confirmed',
            ]);

        $doctorUser = User::factory()->forTenant($tenant)->create(['name' => 'New Doctor']);
        $providerRole = Role::query()->where('slug', 'provider')->whereNull('tenant_id')->firstOrFail();
        $doctorUser->roles()->attach($providerRole->id, [
            'tenant_id' => $tenant->id,
            'branch_id' => null,
            'branch_key' => 'none',
        ]);
        $myProvider = Provider::factory()->forClinic($clinic)->create([
            'user_id' => $doctorUser->id,
            'display_name' => 'New Doctor',
        ]);
        $mine = Appointment::factory()
            ->forProvider($myProvider)
            ->forPatient($patient)
            ->create([
                'clinic_id' => $clinic->id,
                'starts_at' => CarbonImmutable::now()->setTime(11, 0),
                'ends_at' => CarbonImmutable::now()->setTime(11, 30),
                'status' => 'confirmed',
            ]);

        Sanctum::actingAs($doctorUser);
        TenantContext::set($tenant->id);

        $list = $this->getJson('/api/v1/appointments')->assertOk();
        $uuids = collect($list->json('data'))->pluck('uuid');
        $this->assertTrue($uuids->contains($mine->uuid));
        $this->assertCount(1, $uuids);

        Sanctum::actingAs($admin);
        $adminList = $this->getJson('/api/v1/appointments')->assertOk();
        $this->assertGreaterThanOrEqual(2, count($adminList->json('data')));
    }
}
