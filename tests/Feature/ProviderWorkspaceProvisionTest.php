<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ModulePlatformSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProviderWorkspaceProvisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModulePlatformSeeder::class);
    }

    public function test_doctor_persona_provisions_dedicated_tenant_and_free_package(): void
    {
        $discovery = Tenant::factory()->create(['slug' => 'healthassist-demo', 'name' => 'Demo']);
        $user = User::factory()->forTenant($discovery)->create(['name' => 'Patient']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/persona', [
            'persona' => 'doctor',
            'name' => 'Dr. Asha',
            'organization_name' => 'Asha Physio',
            'phone' => '+919999999999',
            'city' => 'Bengaluru',
            'package_key' => 'free',
        ])
            ->assertOk()
            ->assertJsonPath('meta.org_type', 'doctor')
            ->assertJsonPath('meta.role_slug', 'provider');

        $user->refresh();
        $this->assertNotSame($discovery->id, $user->tenant_id);
        $this->assertTrue($user->hasRole('provider'));
        $this->assertDatabaseHas('organizations', [
            'tenant_id' => $user->tenant_id,
            'name' => 'Asha Physio',
            'city' => 'Bengaluru',
        ]);
        $this->assertSame('free', Tenant::query()->find($user->tenant_id)?->plan_code);
        $this->assertNotNull(Package::query()->where('key', 'free')->first());
        $this->assertInstanceOf(Organization::class, Organization::query()->where('tenant_id', $user->tenant_id)->first());
    }

    public function test_setup_status_endpoint_returns_completion(): void
    {
        $discovery = Tenant::factory()->create(['slug' => 'healthassist-demo']);
        $user = User::factory()->forTenant($discovery)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/persona', [
            'persona' => 'clinic',
            'organization_name' => 'City Care',
            'city' => 'Pune',
            'phone' => '+911111111111',
        ])->assertOk();

        $this->getJson('/api/v1/tenants/current/setup-status')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'profile_completion',
                    'onboarding_completed',
                    'sections',
                    'missing',
                    'modules',
                ],
            ]);
    }

    public function test_working_hours_sync_updates_setup_readiness(): void
    {
        $discovery = Tenant::factory()->create(['slug' => 'healthassist-demo']);
        $user = User::factory()->forTenant($discovery)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/persona', [
            'persona' => 'clinic',
            'organization_name' => 'Hours Clinic',
            'phone' => '+912222222222',
            'city' => 'Delhi',
        ])->assertOk();

        $this->putJson('/api/v1/working-hours', [
            'hours' => [
                [
                    'day_of_week' => 1,
                    'opens_at' => '09:00',
                    'closes_at' => '13:00',
                    'break_starts_at' => null,
                    'break_ends_at' => null,
                    'shift_index' => 1,
                    'is_closed' => false,
                ],
                [
                    'day_of_week' => 1,
                    'opens_at' => '16:00',
                    'closes_at' => '20:00',
                    'shift_index' => 2,
                    'is_closed' => false,
                ],
            ],
        ])->assertOk();

        $status = $this->getJson('/api/v1/tenants/current/setup-status')->assertOk()->json('data');
        $hoursSection = collect($status['sections'])->firstWhere('key', 'hours');
        $this->assertTrue($hoursSection['complete']);
        $this->assertTrue($status['modules']['appointments']['ready'] ?? false);
    }
}
