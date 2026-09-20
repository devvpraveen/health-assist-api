<?php

namespace Tests\Feature;

use App\Support\TenantContext;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ClinicLocaleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LanguageSeeder::class);
    }

    public function test_rejects_invalid_default_locale_and_supported_locales(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/clinics', [
            'organization_id' => $organization->id,
            'name' => 'Locale Clinic',
            'slug' => 'locale-clinic',
            'default_locale' => 'es',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['default_locale']);

        $this->postJson('/api/v1/clinics', [
            'organization_id' => $organization->id,
            'name' => 'Locale Clinic 2',
            'slug' => 'locale-clinic-2',
            'default_locale' => 'en',
            'supported_locales' => ['en', 'es'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supported_locales.1']);
    }

    public function test_accepts_enabled_clinic_locales(): void
    {
        [$user, $organization] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/clinics', [
            'organization_id' => $organization->id,
            'name' => 'Bilingual Clinic',
            'slug' => 'bilingual-clinic',
            'default_locale' => 'hi',
            'supported_locales' => ['en', 'hi'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.default_locale', 'hi')
            ->assertJsonPath('data.supported_locales', ['en', 'hi']);
    }
}
