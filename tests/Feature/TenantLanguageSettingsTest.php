<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Support\TenantContext;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class TenantLanguageSettingsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LanguageSeeder::class);
    }

    public function test_tenant_cannot_enable_language_not_platform_enabled_for_scope(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->putJson('/api/v1/tenant/language-settings', [
            'enabled' => ['en', 'es'],
            'default' => 'en',
            'scopes' => [
                'patient_app' => ['en', 'es'],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['enabled']);

        Language::query()->where('code', 'es')->update(['is_enabled' => true]);
        Language::query()->where('code', 'es')->firstOrFail()->scopeAssignments()->updateOrCreate(
            ['scope' => 'clinic_app'],
            ['is_enabled' => true],
        );

        $this->putJson('/api/v1/tenant/language-settings', [
            'enabled' => ['en', 'es'],
            'default' => 'en',
            'scopes' => [
                'patient_app' => ['en', 'es'],
                'clinic_app' => ['en'],
                'public_content' => ['en'],
                'notifications' => ['en'],
                'clinical_content' => ['en'],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scopes.patient_app']);
    }

    public function test_tenant_can_set_subset_of_platform_enabled_languages(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->putJson('/api/v1/tenant/language-settings', [
            'enabled' => ['en'],
            'default' => 'en',
            'scopes' => [
                'patient_app' => ['en'],
                'clinic_app' => ['en'],
                'public_content' => ['en'],
                'notifications' => ['en'],
                'clinical_content' => ['en'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.enabled', ['en'])
            ->assertJsonPath('data.default', 'en');

        $this->getJson('/api/v1/tenant/language-settings')
            ->assertOk()
            ->assertJsonPath('data.enabled', ['en']);
    }
}
