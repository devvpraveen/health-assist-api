<?php

namespace Tests\Feature;

use App\Models\AiModel;
use App\Models\AiModelVersion;
use App\Models\AiProvider;
use App\Models\TenantSetting;
use App\Services\AI\ModelRouter;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class ModelRouterTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_task_type_selects_configured_model_from_registry(): void
    {
        $routed = app(ModelRouter::class)->route('classification');

        $this->assertSame('classification', $routed->taskType);
        $this->assertSame('mock-classify', $routed->model);
        $this->assertSame('1', $routed->modelVersion);
        $this->assertSame('mock', $routed->provider);
        $this->assertArrayHasKey('temperature', $routed->config);
        $this->assertArrayHasKey('max_tokens', $routed->config);
    }

    public function test_embeddings_task_selects_embed_model(): void
    {
        $routed = app(ModelRouter::class)->route('embeddings');

        $this->assertSame('mock-embed', $routed->model);
    }

    public function test_tenant_settings_can_override_model(): void
    {
        [, , $tenant] = $this->createTenantUserWithOrg();

        TenantSetting::query()->create([
            'tenant_id' => $tenant->id,
            'settings' => [
                'ai' => [
                    'models' => [
                        'general_conversation' => [
                            'provider' => 'mock',
                            'model' => 'tenant-override-chat',
                            'model_version' => '9',
                        ],
                    ],
                ],
            ],
        ]);

        $routed = app(ModelRouter::class)->route('general_conversation', $tenant->id);

        $this->assertSame('tenant-override-chat', $routed->model);
        $this->assertSame('9', $routed->modelVersion);
    }

    public function test_merges_version_model_tenant_and_generation_defaults(): void
    {
        $provider = AiProvider::query()->where('key', 'mock')->firstOrFail();
        $model = AiModel::query()->create([
            'provider_id' => $provider->id,
            'key' => 'merge-chat',
            'name' => 'Merge Chat',
            'task_types' => ['marketing'],
            'config' => [
                'temperature' => 0.5,
                'max_tokens' => 900,
                'top_p' => 0.9,
            ],
            'is_custom' => true,
            'external_model_id' => 'vendor-merge-1',
            'is_active' => true,
        ]);

        AiModelVersion::query()->create([
            'model_id' => $model->id,
            'version' => '2',
            'is_default' => true,
            'config' => [
                'temperature' => 0.15,
            ],
        ]);

        [, , $tenant] = $this->createTenantUserWithOrg();

        TenantSetting::query()->create([
            'tenant_id' => $tenant->id,
            'settings' => [
                'ai' => [
                    'models' => [
                        'marketing' => [
                            'provider' => 'mock',
                            'model' => 'merge-chat',
                            'model_version' => '2',
                            'config' => [
                                'max_tokens' => 333,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $routed = app(ModelRouter::class)->route('marketing', $tenant->id);

        $this->assertSame('vendor-merge-1', $routed->model);
        $this->assertSame(0.15, $routed->config['temperature']);
        $this->assertSame(333, $routed->config['max_tokens']);
        $this->assertSame(0.9, $routed->config['top_p']);
    }

    public function test_config_custom_models_entry_is_used_when_registry_misses(): void
    {
        config([
            'ai.custom_models' => [
                'env-chat' => [
                    'provider' => 'openai_compatible',
                    'model' => 'gpt-4o-mini',
                    'model_version' => '1',
                    'task_types' => ['speech'],
                    'config' => [
                        'temperature' => 0.05,
                    ],
                ],
            ],
        ]);

        AiModel::query()->where('key', 'mock-chat')->update([
            'task_types' => [
                'general_conversation',
                'medical_document',
                'clinical_documentation',
                'marketing',
            ],
        ]);

        // Registry must miss `speech` entirely so config custom_models can win.
        AiModel::query()->where('key', 'openai-chat')->update([
            'task_types' => [
                'general_conversation',
                'medical_document',
                'clinical_documentation',
                'marketing',
                'classification',
            ],
        ]);

        $routed = app(ModelRouter::class)->route('speech');

        $this->assertSame('openai_compatible', $routed->provider);
        $this->assertSame('gpt-4o-mini', $routed->model);
        $this->assertSame(0.05, $routed->config['temperature']);
        $this->assertArrayHasKey('max_tokens', $routed->config);
    }
}
