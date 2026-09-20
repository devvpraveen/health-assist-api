<?php

namespace Tests\Feature;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppHandoff;
use App\Models\WhatsAppMessage;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class WhatsAppHandoffTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AiCoreSeeder::class);
        $this->seed(SafetyRulesSeeder::class);

        Http::fake([
            'evolution.test/*' => Http::response(['key' => ['id' => 'out-handoff']], 200),
        ]);
    }

    public function test_talk_to_person_creates_handoff_and_skips_further_ai(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $account = WhatsAppAccount::factory()->forTenant($user->tenant)->create([
            'webhook_secret' => 'secret',
            'instance_name' => 'handoff-inst',
        ]);

        $this->postJson('/api/v1/webhooks/evolution/'.$account->uuid, [
            'event' => 'MESSAGES_UPSERT',
            'instance' => 'handoff-inst',
            'data' => [
                'key' => [
                    'remoteJid' => '5511987654321@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'h1',
                ],
                'message' => ['conversation' => 'Please talk to a person'],
            ],
        ], ['apikey' => 'secret'])->assertOk();

        $conversation = WhatsAppConversation::query()->withoutGlobalScopes()
            ->where('account_id', $account->id)
            ->first();

        $this->assertNotNull($conversation);
        $this->assertSame(WhatsAppConversation::STATE_WAITING_HUMAN, $conversation->state);

        $this->assertDatabaseHas('whatsapp_handoffs', [
            'conversation_id' => $conversation->id,
            'status' => WhatsAppHandoff::STATUS_OPEN,
            'requested_by' => WhatsAppHandoff::REQUESTED_BY_PATIENT,
        ]);

        $outboundCountAfterHandoff = WhatsAppMessage::query()->withoutGlobalScopes()
            ->where('conversation_id', $conversation->id)
            ->where('direction', WhatsAppMessage::DIRECTION_OUTBOUND)
            ->count();
        $this->assertSame(1, $outboundCountAfterHandoff);

        Http::fake([
            'evolution.test/*' => Http::response(['key' => ['id' => 'should-not-ai']], 200),
        ]);

        $this->postJson('/api/v1/webhooks/evolution/'.$account->uuid, [
            'event' => 'MESSAGES_UPSERT',
            'instance' => 'handoff-inst',
            'data' => [
                'key' => [
                    'remoteJid' => '5511987654321@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'h2',
                ],
                'message' => ['conversation' => "I've had severe knee pain for two weeks."],
            ],
        ], ['apikey' => 'secret'])->assertOk();

        $this->assertSame(
            1,
            WhatsAppMessage::query()->withoutGlobalScopes()
                ->where('conversation_id', $conversation->id)
                ->where('direction', WhatsAppMessage::DIRECTION_OUTBOUND)
                ->count()
        );

        $this->assertSame(
            WhatsAppConversation::STATE_WAITING_HUMAN,
            $conversation->fresh()->state
        );
    }
}
