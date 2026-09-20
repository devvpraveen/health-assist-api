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

class LanguagesListTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LanguageSeeder::class);
    }

    public function test_languages_list_filters_by_scope(): void
    {
        $response = $this->getJson('/api/v1/languages?scope=patient_app');

        $response->assertOk()
            ->assertJsonPath('meta.codes', ['en', 'hi'])
            ->assertJsonCount(2, 'data');

        $spanish = Language::query()->where('code', 'es')->firstOrFail();
        $spanish->update(['is_enabled' => true]);
        $spanish->scopeAssignments()->updateOrCreate(
            ['scope' => 'clinic_app'],
            ['is_enabled' => true],
        );

        $this->getJson('/api/v1/languages?scope=clinic_app')
            ->assertOk()
            ->assertJsonPath('meta.codes', ['en', 'hi', 'es']);

        $this->getJson('/api/v1/languages?scope=patient_app')
            ->assertOk()
            ->assertJsonPath('meta.codes', ['en', 'hi']);
    }

    public function test_authenticated_list_respects_tenant_subset(): void
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
        ])->assertOk();

        $this->getJson('/api/v1/languages?scope=patient_app')
            ->assertOk()
            ->assertJsonPath('meta.codes', ['en']);
    }
}
