<?php

namespace Tests\Feature;

use App\Models\HealthGuideConversation;
use App\Models\HealthGuideMessage;
use App\Models\Patient;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class HealthGuideConversationTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);
    }

    public function test_start_and_message_updates_structured_state_and_creates_messages(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $patient = Patient::factory()->forTenant($user->tenant)->create();

        Sanctum::actingAs($user);
        TenantContext::set($user->tenant_id);

        $start = $this->postJson('/api/v1/health-guide/conversations', [
            'patient_id' => $patient->id,
            'locale' => 'en',
        ])->assertCreated()
            ->assertJsonPath('data.status', HealthGuideConversation::STATUS_ACTIVE)
            ->assertJsonPath('data.patient_id', $patient->id);

        $uuid = $start->json('data.uuid');

        $response = $this->postJson("/api/v1/health-guide/conversations/{$uuid}/messages", [
            'content' => "I've had severe knee pain for two weeks.",
        ])->assertOk()
            ->assertJsonPath('structured_state.complaint', 'knee_pain')
            ->assertJsonPath('structured_state.duration', '2_weeks')
            ->assertJsonPath('structured_state.severity', 'severe')
            ->assertJsonPath('structured_state.intent', 'find_provider')
            ->assertJsonStructure([
                'message' => ['uuid', 'role', 'content'],
                'structured_state',
                'safety' => ['level', 'action', 'matched_rules', 'message'],
                'recommendations',
                'disclaimer',
            ]);

        $this->assertSame(HealthGuideMessage::ROLE_ASSISTANT, $response->json('message.role'));
        $this->assertDatabaseCount('health_guide_messages', 2);
        $this->assertDatabaseHas('health_guide_conversations', [
            'uuid' => $uuid,
            'safety_level' => $response->json('safety.level'),
        ]);
    }
}
