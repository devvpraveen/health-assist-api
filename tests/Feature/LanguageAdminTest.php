<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Services\I18n\LanguageCatalog;
use App\Support\TenantContext;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class LanguageAdminTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LanguageSeeder::class);
    }

    public function test_super_admin_can_enable_language_and_assign_scopes(): void
    {
        $admin = $this->createSuperAdminUser();
        Sanctum::actingAs($admin);
        TenantContext::clear();

        $spanish = Language::query()->where('code', 'es')->firstOrFail();

        $this->patchJson('/api/v1/admin/languages/'.$spanish->id, [
            'is_enabled' => true,
            'sort_order' => 10,
        ])
            ->assertOk()
            ->assertJsonPath('data.is_enabled', true)
            ->assertJsonPath('data.code', 'es');

        $this->putJson('/api/v1/admin/languages/'.$spanish->id.'/scopes', [
            'scopes' => [
                ['scope' => 'patient_app', 'is_enabled' => true],
                ['scope' => 'clinic_app', 'is_enabled' => true],
                ['scope' => 'public_content', 'is_enabled' => false],
                ['scope' => 'notifications', 'is_enabled' => true],
                ['scope' => 'clinical_content', 'is_enabled' => false],
            ],
        ])
            ->assertOk();

        $catalog = app(LanguageCatalog::class);

        $this->assertContains('es', $catalog->enabledCodes('patient_app'));
        $this->assertContains('es', $catalog->enabledCodes('clinic_app'));
        $this->assertNotContains('es', $catalog->enabledCodes('public_content'));

        $this->getJson('/api/v1/admin/languages')
            ->assertOk()
            ->assertJsonFragment(['code' => 'es']);
    }

    public function test_org_admin_cannot_manage_platform_languages(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/admin/languages', [
            'code' => 'pt',
            'name' => 'Portuguese',
            'native_name' => 'Português',
        ])->assertForbidden();
    }
}
