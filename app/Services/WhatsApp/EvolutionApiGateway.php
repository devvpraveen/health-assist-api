<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Services\WhatsApp\DTO\SendResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class EvolutionApiGateway implements WhatsAppGatewayInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function sendText(string $instance, string $toE164Digits, string $text, array $options = []): SendResult
    {
        $number = preg_replace('/\D+/', '', $toE164Digits) ?? $toE164Digits;
        $url = $this->baseUrl().'/message/sendText/'.rawurlencode($instance);

        $body = array_key_exists('textMessage', $options)
            ? [
                'number' => $number,
                'textMessage' => is_array($options['textMessage'])
                    ? $options['textMessage']
                    : ['text' => (string) $options['textMessage']],
            ]
            : [
                'number' => $number,
                'text' => $text,
            ];

        if (! isset($body['text']) && isset($body['textMessage']['text'])) {
            // Prefer simple shape when nested was only used for compatibility.
        }

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout((int) config('whatsapp.evolution.timeout', 15))
                ->acceptJson()
                ->asJson()
                ->post($url, $body);
        } catch (ConnectionException $e) {
            return SendResult::failed('Evolution connection error: '.$e->getMessage());
        } catch (Throwable $e) {
            return SendResult::failed('Evolution sendText failed: '.$e->getMessage());
        }

        $json = $response->json() ?? [];
        $messageId = data_get($json, 'key.id')
            ?? data_get($json, 'messageId')
            ?? data_get($json, 'id');

        if (! $response->successful()) {
            return SendResult::failed(
                'Evolution sendText HTTP '.$response->status().': '.$response->body(),
                is_array($json) ? $json : [],
            );
        }

        return SendResult::ok(
            is_string($messageId) ? $messageId : null,
            is_array($json) ? $json : [],
        );
    }

    /**
     * @param  list<string>  $events
     */
    public function setWebhook(string $instance, string $url, array $events = []): void
    {
        $events = $events !== [] ? $events : (array) config('whatsapp.webhook_events', ['MESSAGES_UPSERT']);

        $endpoint = $this->baseUrl().'/webhook/set/'.rawurlencode($instance);

        $payload = [
            'enabled' => true,
            'url' => $url,
            'webhookByEvents' => true,
            'webhookBase64' => false,
            'events' => array_values($events),
        ];

        $response = Http::withHeaders($this->headers())
            ->timeout((int) config('whatsapp.evolution.timeout', 15))
            ->acceptJson()
            ->asJson()
            ->post($endpoint, $payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Evolution setWebhook failed HTTP '.$response->status().': '.$response->body()
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'apikey' => (string) config('whatsapp.evolution.api_key'),
        ];
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('whatsapp.evolution.base_url'), '/');
    }
}
