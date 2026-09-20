<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;
use App\Support\TenantContext;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class EvolutionWebhookInboundTest extends TestCase
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
            'evolution.test/*' => Http::response(['key' => ['id' => 'out-1']], 200),
        ]);
    }

    public function test_messages_upsert_creates_conversation_message_and_replies(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        TenantContext::set($user->tenant_id);

        $account = WhatsAppAccount::factory()->forTenant($user->tenant)->create([
            'instance_name' => 'clinic-wa',
            'webhook_secret' => 'secret-abc',
        ]);

        Patient::factory()->forTenant($user->tenant)->create([
            'phone' => '+55 11 98765-4321',
        ]);

        TenantContext::clear();

        $payload = [
            'event' => 'MESSAGES_UPSERT',
            'instance' => 'clinic-wa',
            'data' => [
                'key' => [
                    'remoteJid' => '5511987654321@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'in-1',
                ],
                'message' => [
                    'conversation' => "I've had severe knee pain for two weeks.",
                ],
            ],
        ];

        $this->postJson('/api/v1/webhooks/evolution/'.$account->uuid, $payload, [
            'apikey' => 'secret-abc',
        ])->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('whatsapp_webhook_events', [
            'account_id' => $account->id,
            'event' => 'MESSAGES_UPSERT',
        ]);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'account_id' => $account->id,
            'remote_phone' => '5511987654321',
        ]);

        $this->assertTrue(
            WhatsAppMessage::query()->withoutGlobalScopes()
                ->where('account_id', $account->id)
                ->where('direction', WhatsAppMessage::DIRECTION_INBOUND)
                ->where('body', "I've had severe knee pain for two weeks.")
                ->exists()
        );

        $this->assertTrue(
            WhatsAppMessage::query()->withoutGlobalScopes()
                ->where('account_id', $account->id)
                ->where('direction', WhatsAppMessage::DIRECTION_OUTBOUND)
                ->exists()
        );

        $event = WhatsAppWebhookEvent::query()->first();
        $this->assertNotNull($event?->processed_at);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/message/sendText/clinic-wa'));
    }

    public function test_rejects_invalid_webhook_secret(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $account = WhatsAppAccount::factory()->forTenant($user->tenant)->create([
            'webhook_secret' => 'expected',
        ]);

        $this->postJson('/api/v1/webhooks/evolution/'.$account->uuid, [
            'event' => 'MESSAGES_UPSERT',
            'data' => [],
        ], [
            'apikey' => 'wrong',
        ])->assertUnauthorized();
    }

    public function test_from_me_does_not_trigger_ai_reply_loop(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $account = WhatsAppAccount::factory()->forTenant($user->tenant)->create([
            'webhook_secret' => 'secret-abc',
        ]);

        $this->postJson('/api/v1/webhooks/evolution/'.$account->uuid, [
            'event' => 'messages.upsert',
            'instance' => $account->instance_name,
            'data' => [
                'key' => [
                    'remoteJid' => '5511987654321@s.whatsapp.net',
                    'fromMe' => true,
                    'id' => 'echo-1',
                ],
                'message' => ['conversation' => 'Staff already sent this'],
            ],
        ], [
            'x-evolution-secret' => 'secret-abc',
        ])->assertOk();

        $conversation = WhatsAppConversation::query()->withoutGlobalScopes()
            ->where('account_id', $account->id)
            ->first();

        $this->assertNotNull($conversation);
        $this->assertSame(1, WhatsAppMessage::query()->withoutGlobalScopes()
            ->where('conversation_id', $conversation->id)
            ->count());
        $this->assertSame(
            WhatsAppMessage::DIRECTION_OUTBOUND,
            WhatsAppMessage::query()->withoutGlobalScopes()->first()->direction
        );

        Http::assertNothingSent();
    }
}
