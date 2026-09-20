<?php

namespace Tests\Feature;

use App\Exceptions\AI\AiQuotaExceededException;
use App\Models\AiUsageRecord;
use App\Services\AI\AiUsageQuotaService;
use App\Services\AI\ModelRouter;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AiUsageQuotaTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_summary_endpoint_returns_period_usage(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();

        AiUsageRecord::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'agent' => 'health_guide',
            'feature' => 'health_guide.conversation',
            'model' => 'mock-chat',
            'model_version' => '1',
            'input_tokens' => 100,
            'output_tokens' => 50,
            'latency_ms' => 12,
            'status' => 'success',
            'estimated_cost_cents' => 1,
            'request_id' => (string) Str::uuid(),
        ]);

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->getJson('/api/v1/ai/usage/summary')
            ->assertOk()
            ->assertJsonPath('data.requests_used', 1)
            ->assertJsonPath('data.tokens_used', 150);
    }

    public function test_quota_service_blocks_when_request_limit_reached(): void
    {
        config([
            'ai.metering.enabled' => true,
            'ai.metering.enforce_quotas' => true,
            'ai.metering.monthly_request_limit' => 1,
            'ai.metering.monthly_token_limit' => 1_000_000,
        ]);

        [$user, , $tenant] = $this->createTenantUserWithOrg();

        AiUsageRecord::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'agent' => 'patient',
            'feature' => 'patient.assist',
            'model' => 'mock-chat',
            'model_version' => '1',
            'input_tokens' => 10,
            'output_tokens' => 10,
            'latency_ms' => 5,
            'status' => 'success',
            'estimated_cost_cents' => 0,
            'request_id' => (string) Str::uuid(),
        ]);

        $this->expectException(AiQuotaExceededException::class);
        app(AiUsageQuotaService::class)->assertWithinQuota($tenant->id);
    }

    public function test_model_router_prefers_default_provider_registry_model(): void
    {
        config(['ai.default_provider' => 'openai_compatible']);

        $routed = app(ModelRouter::class)->route('general_conversation');

        $this->assertSame('openai_compatible', $routed->provider);
        $this->assertNotSame('mock-chat', $routed->model);
    }
}
