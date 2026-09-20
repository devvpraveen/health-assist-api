<?php

namespace Tests\Feature;

use App\Models\AiUsageRecord;
use App\Models\User;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AiSecurityTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_run_requires_authentication(): void
    {
        $this->postJson('/api/v1/ai/agents/patient/run', [
            'input' => 'Hello',
        ])->assertUnauthorized();
    }

    public function test_run_requires_permission(): void
    {
        [, , $tenant] = $this->createTenantUserWithOrg();
        $user = User::factory()->forTenant($tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($tenant->id);

        $this->postJson('/api/v1/ai/agents/patient/run', [
            'input' => 'Hello',
        ])->assertForbidden();
    }

    public function test_usage_does_not_leak_other_tenant_records(): void
    {
        [$userA, , $tenantA] = $this->createTenantUserWithOrg('Tenant A');
        [, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        TenantContext::set($tenantB->id);
        app(AIOrchestrator::class)->run('billing', new AgentContext(
            tenantId: $tenantB->id,
            input: 'secret billing prompt for tenant B',
        ));

        $otherUsage = AiUsageRecord::withoutGlobalScopes()->where('tenant_id', $tenantB->id)->firstOrFail();

        Sanctum::actingAs($userA);
        TenantContext::set($tenantA->id);

        $response = $this->getJson('/api/v1/ai/usage')->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertNotContains($otherUsage->id, $ids);
        $this->assertStringNotContainsString('secret billing prompt', $response->getContent());
    }

    public function test_agents_list_includes_disclaimer(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->getJson('/api/v1/ai/agents')
            ->assertOk()
            ->assertJsonStructure(['data', 'disclaimer'])
            ->assertJsonFragment(['key' => 'patient']);
    }

    public function test_unknown_agent_returns_not_found(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/ai/agents/not-a-real-agent/run', [
            'input' => 'Hello',
        ])->assertNotFound();
    }
}
