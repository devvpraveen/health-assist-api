<?php

namespace Tests\Feature;

use App\Services\AI\ModelRouter;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class TenantAiModelSettingsTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_org_admin_can_put_tenant_ai_model_settings_and_router_uses_config(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->putJson('/api/v1/tenant/ai-model-settings', [
            'default_provider' => 'openai_compatible',
            'models' => [
                'general_conversation' => [
                    'provider' => 'openai_compatible',
                    'model' => 'gpt-4o-mini',
                    'model_version' => '1',
                    'config' => [
                        'temperature' => 0.3,
                        'max_tokens' => 1024,
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.default_provider', 'openai_compatible')
            ->assertJsonPath('data.models.general_conversation.model', 'gpt-4o-mini')
            ->assertJsonPath('data.models.general_conversation.config.temperature', 0.3);

        $this->getJson('/api/v1/tenant/ai-model-settings')
            ->assertOk()
            ->assertJsonPath('data.models.general_conversation.config.max_tokens', 1024);

        $routed = app(ModelRouter::class)->route('general_conversation', $tenant->id);

        $this->assertSame('openai_compatible', $routed->provider);
        $this->assertSame('gpt-4o-mini', $routed->model);
        $this->assertSame('1', $routed->modelVersion);
        $this->assertSame(0.3, $routed->config['temperature']);
        $this->assertSame(1024, $routed->config['max_tokens']);
    }

    public function test_branch_manager_can_view_but_not_update_tenant_ai_settings(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg('Branch', 'branch_manager');

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/tenant/ai-model-settings')->assertOk();

        $this->putJson('/api/v1/tenant/ai-model-settings', [
            'models' => [
                'general_conversation' => [
                    'provider' => 'mock',
                    'model' => 'mock-chat',
                ],
            ],
        ])->assertForbidden();
    }
}
