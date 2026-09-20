<?php

namespace Tests\Feature;

use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class CustomModelConfigRejectedSecretsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_api_key_in_model_config_is_rejected(): void
    {
        $admin = $this->createSuperAdminUser();
        [, , $tenant] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($admin);
        TenantContext::set($tenant->id);

        $this->postJson('/api/v1/ai/models', [
            'provider_key' => 'mock',
            'key' => 'secret-model',
            'name' => 'Secret Model',
            'task_types' => ['general_conversation'],
            'config' => [
                'api_key' => 'sk-test-should-fail',
                'temperature' => 0.2,
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['config']);

        $this->postJson('/api/v1/ai/providers', [
            'key' => 'leaky',
            'name' => 'Leaky Provider',
            'driver' => 'openai_compatible',
            'config' => [
                'api_key' => 'sk-provider',
            ],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['config']);
    }

    public function test_tenant_settings_reject_api_key_in_config(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->putJson('/api/v1/tenant/ai-model-settings', [
            'models' => [
                'general_conversation' => [
                    'provider' => 'openai_compatible',
                    'model' => 'gpt-4o-mini',
                    'config' => [
                        'api_key' => 'sk-tenant',
                    ],
                ],
            ],
        ])->assertStatus(422);
    }
}
