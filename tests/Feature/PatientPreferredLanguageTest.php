<?php

namespace Tests\Feature;

use App\Support\TenantContext;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PatientPreferredLanguageTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(LanguageSeeder::class);
    }

    public function test_rejects_unsupported_preferred_language(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'preferred_language' => 'zz',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['preferred_language']);
    }

    public function test_accepts_enabled_preferred_language(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'preferred_language' => 'hi',
        ])
            ->assertCreated()
            ->assertJsonPath('data.preferred_language', 'hi');
    }
}
