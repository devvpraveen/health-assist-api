<?php

namespace Tests\Feature;

use App\Actions\Modules\AssignPackageToTenantAction;
use App\Models\Package;
use App\Models\Patient;
use App\Services\Mobile\MobileExperienceBuilder;
use App\Support\TenantContext;
use Database\Seeders\ModulePlatformSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class MobileExperienceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_org_admin_gets_clinic_modules_driven_by_permissions(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $response = $this->getJson('/api/v1/mobile/experience');

        $response->assertOk()
            ->assertJsonPath('data.user.uuid', $user->uuid)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.organization.uuid', $organization->uuid)
            ->assertJsonPath('data.active_account_type', null)
            ->assertJsonPath('data.roles.0', 'organization_admin');

        $modules = $response->json('data.modules');
        $this->assertIsArray($modules);
        $this->assertContains('profile', $modules);
        $this->assertContains('notifications', $modules);
        $this->assertContains('patients', $modules);
        $this->assertContains('appointments', $modules);
        $this->assertContains('billing', $modules);
        $this->assertContains('dashboard', $modules);
        $this->assertContains('clinical_notes', $modules);
        $this->assertContains('soap_notes', $modules);
        $this->assertContains('ai_assistant', $modules);
        $this->assertContains('crm', $modules);
        $this->assertContains('marketing', $modules);
        $this->assertContains('settings', $modules);

        $this->assertTrue($response->json('data.entitlements.ai_assistant'));
        $this->assertContains('clinic_owner', $response->json('data.account_types'));
        $this->assertContains('patients.view', $response->json('data.permissions'));
    }

    public function test_permissions_drive_modules_without_role_equality_only(): void
    {
        $builder = app(MobileExperienceBuilder::class);

        $permissions = collect([
            'patients.view',
            'appointments.view',
            'clinical.notes.view',
            'billing.invoices.view',
            'marketing.view',
            'crm.manage',
        ]);

        $modules = $builder->mapModules($permissions, false);

        $this->assertContains('patients', $modules);
        $this->assertContains('appointments', $modules);
        $this->assertContains('soap_notes', $modules);
        $this->assertContains('clinical_notes', $modules);
        $this->assertContains('billing', $modules);
        $this->assertContains('marketing', $modules);
        $this->assertContains('crm', $modules);
        $this->assertContains('profile', $modules);
        $this->assertContains('notifications', $modules);
        $this->assertNotContains('settings', $modules);
    }

    public function test_linked_patient_adds_patient_modules(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Patient::factory()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'first_name' => 'Linked',
            'last_name' => 'Patient',
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $response = $this->getJson('/api/v1/mobile/experience');

        $response->assertOk();
        $modules = $response->json('data.modules');
        $this->assertContains('home', $modules);
        $this->assertContains('records', $modules);
        $this->assertContains('medications', $modules);
        $this->assertTrue($response->json('data.has_linked_patient'));
        $this->assertContains('patient', $response->json('data.account_types'));
        $this->assertTrue($response->json('data.feature_flags.workspace_switcher'));
    }

    public function test_onboarding_stores_account_type_and_refreshes_experience(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $response = $this->postJson('/api/v1/mobile/onboarding/account-type', [
            'account_type' => 'clinic_owner',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.active_account_type', 'clinic_owner')
            ->assertJsonPath('data.professional_type', null);

        $this->assertDatabaseHas('mobile_user_preferences', [
            'user_id' => $user->id,
            'account_type' => 'clinic_owner',
        ]);

        $this->getJson('/api/v1/mobile/experience')
            ->assertOk()
            ->assertJsonPath('data.active_account_type', 'clinic_owner');
    }

    public function test_healthcare_professional_requires_professional_type(): void
    {
        [$user] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/mobile/onboarding/account-type', [
            'account_type' => 'healthcare_professional',
        ])->assertStatus(422);

        $this->postJson('/api/v1/mobile/onboarding/account-type', [
            'account_type' => 'healthcare_professional',
            'professional_type' => 'physiotherapist',
        ])->assertOk()
            ->assertJsonPath('data.active_account_type', 'healthcare_professional')
            ->assertJsonPath('data.professional_type', 'physiotherapist');
    }

    public function test_guest_cannot_load_experience(): void
    {
        $this->getJson('/api/v1/mobile/experience')->assertUnauthorized();
    }

    public function test_experience_filters_modules_by_active_tenant_package(): void
    {
        $this->seed(ModulePlatformSeeder::class);

        [$user, , $tenant] = $this->createTenantUserWithOrg('Starter Experience Clinic');
        $starter = Package::query()->where('key', 'starter')->firstOrFail();
        app(AssignPackageToTenantAction::class)->handle($tenant, $starter, true);

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $modules = $this->getJson('/api/v1/mobile/experience')
            ->assertOk()
            ->json('data.modules');

        $this->assertContains('patients', $modules);
        $this->assertContains('appointments', $modules);
        $this->assertContains('profile', $modules);
        $this->assertNotContains('billing', $modules);
        $this->assertNotContains('clinical_notes', $modules);
        $this->assertNotContains('soap_notes', $modules);
        $this->assertNotContains('ai_assistant', $modules);
        $this->assertNotContains('messages', $modules);
        $this->assertNotContains('crm', $modules);
        $this->assertNotContains('marketing', $modules);

        $this->assertFalse($this->getJson('/api/v1/mobile/experience')->json('data.entitlements.ai_assistant'));
    }
}
