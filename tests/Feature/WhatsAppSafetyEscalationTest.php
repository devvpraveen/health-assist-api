<?php

namespace Tests\Feature;

use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use Database\Seeders\AiCoreSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SafetyRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesTenantUsers;
use Tests\TestCase;

class WhatsAppSafetyEscalationTest extends TestCase
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
            'evolution.test/*' => Http::response(['key' => ['id' => 'safety-out']], 200),
        ]);
    }

    public function test_emergency_inbound_replies_with_escalation_copy(): void
    {
        [$user] = $this->createTenantUserWithOrg();
        $account = WhatsAppAccount::factory()->forTenant($user->tenant)->create([
            'webhook_secret' => 'secret',
            'instance_name' => 'safety-inst',
        ]);

        $this->postJson('/api/v1/webhooks/evolution/'.$account->uuid, [
            'event' => 'MESSAGES_UPSERT',
            'instance' => 'safety-inst',
            'data' => [
                'key' => [
                    'remoteJid' => '5511987654321@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 's1',
                ],
                'message' => [
                    'conversation' => 'I have crushing chest pain and difficulty breathing',
                ],
            ],
        ], ['apikey' => 'secret'])->assertOk();

        $outbound = WhatsAppMessage::query()->withoutGlobalScopes()
            ->where('account_id', $account->id)
            ->where('direction', WhatsAppMessage::DIRECTION_OUTBOUND)
            ->first();

        $this->assertNotNull($outbound);
        $this->assertStringContainsString('emergency', mb_strtolower((string) $outbound->body));

        Http::assertSent(function ($request) {
            $text = (string) ($request['text'] ?? '');

            return str_contains($request->url(), '/message/sendText/safety-inst')
                && str_contains(mb_strtolower($text), 'emergency');
        });
    }
}
