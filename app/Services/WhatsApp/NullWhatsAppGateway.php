<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Services\WhatsApp\DTO\SendResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NullWhatsAppGateway implements WhatsAppGatewayInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function sendText(string $instance, string $toE164Digits, string $text, array $options = []): SendResult
    {
        Log::debug('NullWhatsAppGateway sendText', [
            'instance' => $instance,
            'to' => $toE164Digits,
            'text_length' => mb_strlen($text),
        ]);

        return SendResult::ok('null-'.Str::uuid()->toString(), [
            'driver' => 'null',
            'instance' => $instance,
            'to' => $toE164Digits,
        ]);
    }

    /**
     * @param  list<string>  $events
     */
    public function setWebhook(string $instance, string $url, array $events = []): void
    {
        Log::debug('NullWhatsAppGateway setWebhook', [
            'instance' => $instance,
            'url' => $url,
            'events' => $events,
        ]);
    }
}
