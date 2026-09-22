<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PlatformThemeTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_theme_returns_health_assist_default(): void
    {
        $this->getJson('/api/v1/theme')
            ->assertOk()
            ->assertJsonPath('data.preset', 'health_assist_default')
            ->assertJsonPath('data.brand.appName', 'Health Assist')
            ->assertJsonPath('data.colors.primary', '#091E3A')
            ->assertJsonPath('data.colors.secondary', '#0D9488')
            ->assertJsonPath('data.colors.accent', '#0EA5E9')
            ->assertJsonPath('data.density', 'comfortable');
    }

    public function test_admin_can_update_draft_and_publish_theme(): void
    {
        $admin = $this->createSuperAdminUser();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/theme')
            ->assertOk()
            ->assertJsonPath('data.draft.colors.primary', '#091E3A');

        $this->putJson('/api/v1/admin/theme/draft', [
            'preset' => 'custom',
            'colors' => [
                'primary' => '#0F2D59',
                'secondary' => '#0D9488',
            ],
            'density' => 'compact',
            'shape' => [
                'radiusPreset' => 'soft',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.draft.colors.primary', '#0F2D59')
            ->assertJsonPath('data.draft.density', 'compact')
            ->assertJsonPath('data.draft.shape.radiusPreset', 'soft')
            ->assertJsonPath('data.published.colors.primary', '#091E3A');

        $this->postJson('/api/v1/admin/theme/publish')
            ->assertOk()
            ->assertJsonPath('data.published.colors.primary', '#0F2D59')
            ->assertJsonPath('data.published.density', 'compact');

        $this->getJson('/api/v1/theme')
            ->assertOk()
            ->assertJsonPath('data.colors.primary', '#0F2D59')
            ->assertJsonPath('data.density', 'compact');
    }

    public function test_named_preset_keeps_preset_id_when_colors_are_included(): void
    {
        $admin = $this->createSuperAdminUser();
        Sanctum::actingAs($admin);

        $this->putJson('/api/v1/admin/theme/draft', [
            'preset' => 'clinical_blue',
            'colors' => [
                'primary' => '#0F2D59',
                'secondary' => '#1D4ED8',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.draft.preset', 'clinical_blue')
            ->assertJsonPath('data.draft.colors.primary', '#0F2D59');
    }

    public function test_non_admin_cannot_manage_theme(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/theme')->assertForbidden();
        $this->putJson('/api/v1/admin/theme/draft', ['density' => 'standard'])->assertForbidden();
        $this->postJson('/api/v1/admin/theme/publish')->assertForbidden();
    }
}
