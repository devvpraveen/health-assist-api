<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ModulePlatformSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChoosePersonaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ModulePlatformSeeder::class);
    }

    public function test_user_can_choose_patient_persona_once(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create(['name' => 'Patient']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/persona', [
            'persona' => 'patient',
            'name' => 'Asha',
        ])
            ->assertOk()
            ->assertJsonPath('meta.persona', 'patient')
            ->assertJsonPath('meta.role_slug', 'patient')
            ->assertJsonPath('data.name', 'Asha');

        $this->assertTrue($user->fresh()->hasRole('patient'));

        $this->postJson('/api/v1/auth/persona', [
            'persona' => 'provider',
        ])->assertStatus(422);
    }

    public function test_provider_and_clinic_personas_assign_roles(): void
    {
        $tenant = Tenant::factory()->create();

        $doctor = User::factory()->forTenant($tenant)->create();
        Sanctum::actingAs($doctor);
        $this->postJson('/api/v1/auth/persona', ['persona' => 'provider'])
            ->assertOk()
            ->assertJsonPath('meta.role_slug', 'provider');
        $this->assertTrue($doctor->fresh()->hasRole('provider'));
        $doctor = $doctor->fresh();
        $this->assertDatabaseHas('providers', [
            'user_id' => $doctor->id,
            'tenant_id' => $doctor->tenant_id,
        ]);
        $this->assertInstanceOf(Provider::class, Provider::query()->where('user_id', $doctor->id)->first());

        $clinicUser = User::factory()->forTenant($tenant)->create();
        Sanctum::actingAs($clinicUser);
        $this->postJson('/api/v1/auth/persona', ['persona' => 'clinic'])
            ->assertOk()
            ->assertJsonPath('meta.role_slug', 'clinic_admin');
        $this->assertTrue($clinicUser->fresh()->hasRole('clinic_admin'));

        $this->assertNotNull(Role::query()->where('slug', 'clinic_admin')->first());
    }
}
