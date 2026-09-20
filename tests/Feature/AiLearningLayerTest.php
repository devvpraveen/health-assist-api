<?php

namespace Tests\Feature;

use App\Models\AiFeedback;
use App\Models\AiKnowledgeDocument;
use App\Models\AiLearningCandidate;
use App\Models\AiLearningSignal;
use App\Services\AI\AgentRegistry;
use App\Services\AI\LayerCCatalog;
use App\Services\AI\Learning\LearningEngine;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class AiLearningLayerTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
    }

    public function test_layer_c_agents_are_registered(): void
    {
        $registry = app(AgentRegistry::class);
        foreach (LayerCCatalog::LAYER_C_KEYS as $key) {
            $this->assertTrue($registry->has($key), "Missing Layer C agent [{$key}]");
        }

        $list = collect($registry->list());
        $this->assertTrue($list->contains(fn ($a) => $a['key'] === 'physiotherapy' && $a['layer_c'] === true));
        $this->assertTrue($list->contains(fn ($a) => $a['key'] === 'review' && $a['layer_c'] === true));
        $this->assertTrue($list->contains(fn ($a) => $a['key'] === 'lead_qualification' && $a['layer_c'] === true));
    }

    public function test_feedback_creates_signal_and_candidate_without_weight_update(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $response = $this->postJson('/api/v1/ai/feedback', [
            'agent' => 'clinical',
            'source' => 'doctor',
            'original_output' => 'Possible muscular strain',
            'corrected_output' => 'Mechanical low back pain with suspected muscular involvement',
        ])->assertCreated();

        $this->assertDatabaseHas('ai_feedback', [
            'id' => $response->json('data.id'),
            'agent' => 'clinical',
        ]);
        $this->assertDatabaseHas('ai_learning_signals', [
            'agent' => 'clinical',
            'signal_type' => 'clinician_corrected',
        ]);
        $this->assertDatabaseHas('ai_learning_candidates', [
            'agent' => 'clinical',
            'status' => AiLearningCandidate::STATUS_PENDING_REVIEW,
        ]);
    }

    public function test_learning_summary_and_evaluation_endpoints(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        app(LearningEngine::class)->recordSignal(
            tenantId: $user->tenant_id,
            agent: 'health_guide',
            signalType: 'patient_helpful',
            source: 'patient',
            actorUserId: $user->id,
        );

        $this->getJson('/api/v1/ai/learning/summary')
            ->assertOk()
            ->assertJsonPath('data.signals_by_type.patient_helpful', 1);

        $this->getJson('/api/v1/ai/evaluation?refresh=1')
            ->assertOk()
            ->assertJsonStructure(['data' => ['overall_score', 'groundedness', 'safety', 'helpfulness', 'note']]);
    }

    public function test_approved_knowledge_is_retrievable(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        AiKnowledgeDocument::query()->create([
            'tenant_id' => $user->tenant_id,
            'title' => 'Knee physiotherapy basics',
            'source_type' => 'guideline',
            'status' => AiKnowledgeDocument::STATUS_APPROVED,
            'body' => 'Stair climbing may aggravate right knee pain; escalate if swelling worsens.',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $this->getJson('/api/v1/ai/knowledge/retrieve?q=knee+stairs')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Knee physiotherapy basics']);
    }

    public function test_orchestrator_run_does_not_claim_weight_update(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $this->postJson('/api/v1/ai/agents/patient/run', [
            'input' => 'My right knee hurts on stairs',
            'metadata' => ['conversation_ref' => 'test-conv-1'],
        ])
            ->assertOk()
            ->assertJsonPath('data.meta.learning.weights_updated', false);
    }
}
