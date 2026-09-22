<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
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

    public function test_super_admin_can_manage_roles_and_permissions(): void
    {
        $admin = User::query()->where('email', 'super@healthassist.test')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/permissions')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'slug']]]);

        $create = $this->postJson('/api/v1/admin/roles', [
            'name' => 'Platform Billing',
            'slug' => 'platform_billing',
            'permission_slugs' => ['billing.invoices.view', 'billing.payments.view', 'users.view'],
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'platform_billing');

        $roleId = $create->json('data.id');

        $this->patchJson("/api/v1/admin/roles/{$roleId}", [
            'permission_slugs' => ['billing.invoices.view', 'billing.invoices.manage', 'users.manage'],
        ])->assertOk()
            ->assertJsonPath('data.permission_slugs', function ($slugs) {
                return is_array($slugs)
                    && in_array('billing.invoices.manage', $slugs, true)
                    && ! in_array('billing.payments.view', $slugs, true);
            });

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
}
