<?php

namespace App\Contracts\WhatsApp;

use App\Services\WhatsApp\DTO\SendResult;

interface WhatsAppGatewayInterface
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function sendText(string $instance, string $toE164Digits, string $text, array $options = []): SendResult;

    /**
     * @param  list<string>  $events
     */
    public function setWebhook(string $instance, string $url, array $events = []): void;
}
