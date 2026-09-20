<?php

namespace Tests\Feature;

use App\Services\WhatsApp\EvolutionApiGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvolutionGatewaySendTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_text_posts_simple_body_with_apikey_header(): void
    {
        Http::fake([
            'evolution.test/message/sendText/*' => Http::response([
                'key' => ['id' => 'msg-123'],
            ], 200),
        ]);

        config([
            'whatsapp.driver' => 'evolution',
            'whatsapp.evolution.base_url' => 'http://evolution.test',
            'whatsapp.evolution.api_key' => 'test-evolution-key',
        ]);

        $gateway = new EvolutionApiGateway;
        $result = $gateway->sendText('clinic-a', '5511999999999', 'Hello');

        $this->assertTrue($result->success);
        $this->assertSame('msg-123', $result->messageId);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://evolution.test/message/sendText/clinic-a'
                && $request->hasHeader('apikey', 'test-evolution-key')
                && $request['number'] === '5511999999999'
                && $request['text'] === 'Hello';
        });
    }

    public function test_send_text_supports_nested_text_message_shape(): void
    {
        Http::fake([
            'evolution.test/*' => Http::response(['key' => ['id' => 'nested-1']], 200),
        ]);

        config([
            'whatsapp.evolution.base_url' => 'http://evolution.test',
            'whatsapp.evolution.api_key' => 'test-evolution-key',
        ]);

        $gateway = new EvolutionApiGateway;
        $result = $gateway->sendText('clinic-a', '+55 (11) 99999-9999', 'Hi', [
            'textMessage' => ['text' => 'Hi'],
        ]);

        $this->assertTrue($result->success);

        Http::assertSent(function ($request) {
            return $request['number'] === '5511999999999'
                && ($request['textMessage']['text'] ?? null) === 'Hi';
        });
    }

    public function test_set_webhook_posts_expected_payload(): void
    {
        Http::fake([
            'evolution.test/webhook/set/*' => Http::response(['ok' => true], 200),
        ]);

        config([
            'whatsapp.evolution.base_url' => 'http://evolution.test',
            'whatsapp.evolution.api_key' => 'test-evolution-key',
        ]);

        $gateway = new EvolutionApiGateway;
        $gateway->setWebhook('clinic-a', 'https://app.test/api/v1/webhooks/evolution/abc', [
            'MESSAGES_UPSERT',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/webhook/set/clinic-a')
                && $request['enabled'] === true
                && $request['url'] === 'https://app.test/api/v1/webhooks/evolution/abc'
                && $request['webhookByEvents'] === true
                && $request['webhookBase64'] === false
                && in_array('MESSAGES_UPSERT', $request['events'], true);
        });
    }
}
