<?php

namespace App\Jobs;

use App\Models\WhatsAppWebhookEvent;
use App\Services\WhatsApp\InboundWhatsAppProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessEvolutionWhatsAppWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $webhookEventId) {}

    public function handle(InboundWhatsAppProcessor $processor): void
    {
        $event = WhatsAppWebhookEvent::query()->find($this->webhookEventId);

        if ($event === null || $event->processed_at !== null) {
            return;
        }

        $processor->processWebhookEvent($event);
    }
}
