<?php

namespace Tests\Feature;

use App\Models\AiModel;
use App\Models\AiProvider;
use App\Services\AI\ModelRouter;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AdminCustomModelTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_admin_can_create_custom_model_and_router_picks_it(): void
    {
        $admin = $this->createSuperAdminUser();
        [, , $tenant] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($admin);
        TenantContext::set($tenant->id);

        $providerResponse = $this->postJson('/api/v1/ai/providers', [
            'key' => 'custom_openai',
            'name' => 'Custom OpenAI Compatible',
            'driver' => 'openai_compatible',
            'config' => ['base_url' => 'https://api.example.test/v1'],
            'is_active' => true,
        ])->assertCreated();

        $providerId = $providerResponse->json('data.id');

        $create = $this->postJson('/api/v1/ai/models', [
            'provider_id' => $providerId,
            'key' => 'clinic-chat',
            'name' => 'Clinic Chat',
            'task_types' => ['general_conversation'],
            'external_model_id' => 'gpt-4o-mini',
            'is_custom' => true,
            'config' => [
                'temperature' => 0.4,
                'max_tokens' => 512,
            ],
            'version' => '1',
            'version_config' => [
                'temperature' => 0.1,
            ],
        ])->assertCreated()
            ->assertJsonPath('data.key', 'clinic-chat')
            ->assertJsonPath('data.is_custom', true)
            ->assertJsonPath('data.external_model_id', 'gpt-4o-mini')
            ->assertJsonPath('data.config.temperature', 0.4);

        $modelId = $create->json('data.id');

        $this->getJson('/api/v1/ai/models')
            ->assertOk()
            ->assertJsonFragment(['key' => 'clinic-chat']);

        $this->assertDatabaseHas('ai_models', [
            'id' => $modelId,
            'is_custom' => true,
            'external_model_id' => 'gpt-4o-mini',
        ]);

        $routed = app(ModelRouter::class)->route('general_conversation');

        $this->assertSame('custom_openai', $routed->provider);
        $this->assertSame('gpt-4o-mini', $routed->model);
        $this->assertSame(0.1, $routed->config['temperature']);
        $this->assertSame(512, $routed->config['max_tokens']);
    }

    public function test_include_inactive_requires_manage_permission(): void
    {
        $admin = $this->createSuperAdminUser();
        [$orgAdmin, , $tenant] = $this->createTenantUserWithOrg();

        $provider = AiProvider::query()->where('key', 'mock')->firstOrFail();
        AiModel::query()->create([
            'provider_id' => $provider->id,
            'key' => 'inactive-custom',
            'name' => 'Inactive Custom',
            'task_types' => ['marketing'],
            'is_custom' => true,
            'is_active' => false,
        ]);

        Sanctum::actingAs($admin);
        TenantContext::set($tenant->id);

        $keys = collect($this->getJson('/api/v1/ai/models?include_inactive=1')->assertOk()->json('data'))
            ->pluck('key')
            ->all();
        $this->assertContains('inactive-custom', $keys);

        Sanctum::actingAs($orgAdmin);
        TenantContext::set($tenant->id);

        $keys = collect($this->getJson('/api/v1/ai/models?include_inactive=1')->assertOk()->json('data'))
            ->pluck('key')
            ->all();
        $this->assertNotContains('inactive-custom', $keys);
    }
}
