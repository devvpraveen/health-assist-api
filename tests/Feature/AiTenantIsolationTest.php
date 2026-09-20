<?php

namespace Tests\Feature;

use App\Models\AiAuditLog;
use App\Models\AiUsageRecord;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AiTenantIsolationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_usage_and_audit_are_scoped_to_tenant(): void
    {
        [$userA, , $tenantA] = $this->createTenantUserWithOrg('Tenant A');
        [$userB, , $tenantB] = $this->createTenantUserWithOrg('Tenant B');

        TenantContext::set($tenantA->id);
        app(AIOrchestrator::class)->run('patient', new AgentContext(
            tenantId: $tenantA->id,
            userId: $userA->id,
            input: 'Tenant A message',
        ));

        TenantContext::set($tenantB->id);
        app(AIOrchestrator::class)->run('patient', new AgentContext(
            tenantId: $tenantB->id,
            userId: $userB->id,
            input: 'Tenant B message',
        ));

        Sanctum::actingAs($userA);
        TenantContext::set($tenantA->id);

        $usage = $this->getJson('/api/v1/ai/usage')->assertOk();
        $this->assertCount(1, $usage->json('data'));
        $this->assertSame($tenantA->id, $usage->json('data.0.tenant_id'));

        $audit = $this->getJson('/api/v1/ai/audit-logs')->assertOk();
        $this->assertCount(1, $audit->json('data'));
        $this->assertSame($tenantA->id, $audit->json('data.0.tenant_id'));

        $this->assertSame(1, AiUsageRecord::query()->count());
        $this->assertSame(1, AiAuditLog::query()->count());
        $this->assertSame(2, AiUsageRecord::withoutGlobalScopes()->count());
        $this->assertSame(2, AiAuditLog::withoutGlobalScopes()->count());
    }
}
