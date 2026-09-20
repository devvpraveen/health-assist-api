<?php

namespace Tests\Feature;

use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class PromptVersioningTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_activate_version_archives_previous_active_and_run_uses_active(): void
    {
        $superAdmin = $this->createSuperAdminUser();
        [, , $tenant] = $this->createTenantUserWithOrg();

        Sanctum::actingAs($superAdmin);
        TenantContext::set($tenant->id);

        $prompt = AiPrompt::query()->where('key', 'patient')->firstOrFail();

        $create = $this->postJson('/api/v1/ai/prompts/'.$prompt->id.'/versions', [
            'system_prompt' => 'You are Health Assist patient assistant v2. Do not diagnose or prescribe.',
            'status' => 'draft',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.status', AiPromptVersion::STATUS_DRAFT);

        $versionId = $create->json('data.id');

        $this->postJson('/api/v1/ai/prompt-versions/'.$versionId.'/activate')
            ->assertOk()
            ->assertJsonPath('data.status', AiPromptVersion::STATUS_ACTIVE)
            ->assertJsonPath('data.version', 2);

        $this->assertSame(
            AiPromptVersion::STATUS_ARCHIVED,
            AiPromptVersion::query()->where('prompt_id', $prompt->id)->where('version', 1)->value('status'),
        );

        $this->assertSame(
            1,
            AiPromptVersion::query()
                ->where('prompt_id', $prompt->id)
                ->where('status', AiPromptVersion::STATUS_ACTIVE)
                ->count(),
        );

        $result = app(AIOrchestrator::class)->run('patient', new AgentContext(
            tenantId: $tenant->id,
            userId: $superAdmin->id,
            input: 'Check prompt version',
        ));

        $this->assertSame(2, $result->promptVersion);
        $this->assertSame('patient', $result->promptKey);
    }

    public function test_branch_manager_cannot_manage_prompts(): void
    {
        [$user] = $this->createTenantUserWithOrg('Branch Tenant', 'branch_manager');
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $prompt = AiPrompt::query()->where('key', 'patient')->firstOrFail();

        $this->postJson('/api/v1/ai/prompts/'.$prompt->id.'/versions', [
            'system_prompt' => 'Should fail',
        ])->assertForbidden();
    }
}
