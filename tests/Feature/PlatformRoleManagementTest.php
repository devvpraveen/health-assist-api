<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\RoleCatalog;
use Database\Seeders\DemoTenantSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoTenantSeeder::class);
    }

    public function test_super_admin_can_manage_platform_roles_only(): void
    {
        $admin = User::query()->where('email', 'super@healthassist.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $index = $this->getJson('/api/v1/admin/roles')
            ->assertOk();

        $slugs = collect($index->json('data'))->pluck('slug')->all();
        $this->assertContains('super_admin', $slugs);
        $this->assertNotContains('patient', $slugs);
        $this->assertNotContains('provider', $slugs);
        $this->assertNotContains('clinic_admin', $slugs);

        $create = $this->postJson('/api/v1/admin/roles', [
            'name' => 'Billing Admin',
            'slug' => 'billing',
            'permission_slugs' => ['billing.invoices.view', 'billing.payments.view', 'users.view'],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'platform_billing')
            ->assertJsonPath('data.is_platform_admin', true);

        $roleId = $create->json('data.id');

        $this->patchJson("/api/v1/admin/roles/{$roleId}", [
            'permission_slugs' => ['billing.invoices.view', 'billing.invoices.manage', 'users.manage'],
        ])->assertOk();

        $this->postJson('/api/v1/admin/users', [
            'name' => 'Billing Admin',
            'email' => 'billing.admin@healthassist.test',
            'password' => 'password123',
            'role_slug' => 'platform_billing',
            'platform' => true,
        ])->assertCreated()
            ->assertJsonPath('data.email', 'billing.admin@healthassist.test')
            ->assertJsonPath('data.tenant_id', null);

        $this->getJson('/api/v1/admin/users?audience=admin')
            ->assertOk()
            ->assertJsonFragment(['email' => 'billing.admin@healthassist.test']);

        $this->deleteJson("/api/v1/admin/roles/{$roleId}")
            ->assertStatus(422);

        $billingUser = User::query()->where('email', 'billing.admin@healthassist.test')->firstOrFail();
        $billingUser->roles()->detach();

        $this->deleteJson("/api/v1/admin/roles/{$roleId}")
            ->assertNoContent();
    }

    public function test_platform_admin_cannot_mutate_tenant_template_roles(): void
    {
        $admin = User::query()->where('email', 'super@healthassist.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $provider = Role::query()->whereNull('tenant_id')->where('slug', 'provider')->firstOrFail();

        $this->patchJson("/api/v1/admin/roles/{$provider->id}", [
            'name' => 'Hacked',
        ])->assertNotFound();

        $this->deleteJson("/api/v1/admin/roles/{$provider->id}")
            ->assertNotFound();
    }

    public function test_super_admin_role_cannot_be_deleted_or_have_slug_changed(): void
    {
        $admin = User::query()->where('email', 'super@healthassist.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $role = Role::query()->where('slug', 'super_admin')->whereNull('tenant_id')->firstOrFail();

        $this->patchJson("/api/v1/admin/roles/{$role->id}", [
            'slug' => 'not_super',
        ])->assertStatus(422);

        $this->deleteJson("/api/v1/admin/roles/{$role->id}")
            ->assertStatus(422);

        $this->assertTrue(
            $role->fresh()->permissions()->count() >= Permission::query()->count()
        );
    }

    public function test_tenant_admin_manages_own_users_and_custom_roles(): void
    {
        $clinicAdmin = User::query()->where('email', 'admin@healthassist.test')->firstOrFail();
        Sanctum::actingAs($clinicAdmin);

        $this->getJson('/api/v1/staff/roles')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'provider'])
            ->assertJsonFragment(['slug' => 'clinic_admin']);

        $createRole = $this->postJson('/api/v1/staff/roles', [
            'name' => 'Front Desk',
            'slug' => 'front_desk',
            'permission_slugs' => ['appointments.view', 'patients.view'],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'front_desk')
            ->assertJsonPath('data.scope', 'tenant');

        $roleId = $createRole->json('data.id');

        $this->postJson('/api/v1/staff/users', [
            'name' => 'Desk Staff',
            'email' => 'frontdesk@healthassist.test',
            'password' => 'password123',
            'role_slug' => 'front_desk',
        ])->assertCreated()
            ->assertJsonPath('data.email', 'frontdesk@healthassist.test')
            ->assertJsonPath('data.tenant_id', $clinicAdmin->tenant_id);

        $this->getJson('/api/v1/staff/users')
            ->assertOk()
            ->assertJsonFragment(['email' => 'frontdesk@healthassist.test']);

        $this->postJson('/api/v1/staff/users', [
            'name' => 'Bad Platform',
            'email' => 'bad.platform@healthassist.test',
            'password' => 'password123',
            'role_slug' => 'super_admin',
        ])->assertStatus(422);

        $this->postJson('/api/v1/staff/roles', [
            'name' => 'Hijack',
            'slug' => 'platform_evil',
        ])->assertStatus(422);

        $this->deleteJson("/api/v1/staff/roles/{$roleId}")
            ->assertStatus(422);

        $staff = User::query()->where('email', 'frontdesk@healthassist.test')->firstOrFail();
        $staff->roles()->detach();

        $this->deleteJson("/api/v1/staff/roles/{$roleId}")
            ->assertNoContent();

        $this->assertTrue(RoleCatalog::isPlatformAdminSlug('super_admin'));
        $this->assertTrue(RoleCatalog::isTenantTemplateSlug('provider'));
    }
}
