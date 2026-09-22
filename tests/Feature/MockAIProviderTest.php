<?php

namespace Tests\Feature;

use App\Models\AiAuditLog;
use App\Models\AiUsageRecord;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Services\AI\DTO\PromptRequest;
use App\Services\AI\Providers\MockAIProvider;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class MockAIProviderTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_mock_provider_returns_deterministic_content(): void
    {
        $provider = app(MockAIProvider::class);
        $result = $provider->generate(new PromptRequest(
            tenantId: 1,
            agent: 'patient',
            feature: 'patient.assist',
            taskType: 'general_conversation',
            messages: [['role' => 'user', 'content' => 'Hello']],
            system: 'You are Health Assist patient assistant. Do not diagnose or prescribe.',
            modelHint: 'mock-chat',
        ));

        $this->assertStringContainsString('Based on the information you shared', $result->content);
        $this->assertStringContainsString('Hello', $result->content);
        $this->assertStringNotContainsString('Structured state summary', $result->content);
        $this->assertSame('mock-chat', $result->model);
        $this->assertGreaterThan(0, $result->inputTokens);
        $this->assertGreaterThan(0, $result->outputTokens);
    }

    public function test_orchestrator_patient_agent_creates_usage_and_audit(): void
    {
        [$user, , $tenant] = $this->createTenantUserWithOrg();
        TenantContext::set($tenant->id);

        $result = app(AIOrchestrator::class)->run('patient', new AgentContext(
            tenantId: $tenant->id,
            userId: $user->id,
            input: 'I need help finding a clinic',
        ));

        $this->assertNotEmpty($result->content);
        $this->assertSame('patient', $result->agent);
        $this->assertSame('patient', $result->promptKey);
        $this->assertSame(1, $result->promptVersion);
        $this->assertNotNull($result->usageRecordId);
        $this->assertNotNull($result->auditLogId);
        $this->assertStringContainsString('assistive', strtolower($result->disclaimer));

        $this->assertDatabaseHas('ai_usage_records', [
            'id' => $result->usageRecordId,
            'tenant_id' => $tenant->id,
            'agent' => 'patient',
        ]);

        $this->assertDatabaseHas('ai_audit_logs', [
            'id' => $result->auditLogId,
            'tenant_id' => $tenant->id,
            'agent' => 'patient',
            'review_status' => AiAuditLog::REVIEW_NOT_REQUIRED,
        ]);

        $this->assertSame(1, AiUsageRecord::query()->count());
        $this->assertSame(1, AiAuditLog::query()->count());
    }

    public function test_api_run_patient_agent_returns_content(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/ai/agents/patient/run', [
            'input' => 'Hello Health Assist',
        ])
            ->assertOk()
            ->assertJsonPath('data.agent', 'patient')
            ->assertJsonStructure([
                'data' => [
                    'content',
                    'usage_record_id',
                    'audit_log_id',
                    'prompt_version',
                    'disclaimer',
                ],
                'disclaimer',
            ]);
    }
}
