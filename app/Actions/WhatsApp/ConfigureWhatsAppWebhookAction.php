<?php

namespace App\Actions\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Models\WhatsAppAccount;
use App\Services\AuditLogger;

class ConfigureWhatsAppWebhookAction
{
    public function __construct(
        private WhatsAppGatewayInterface $gateway,
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  list<string>|null  $events
     */
    public function handle(WhatsAppAccount $account, ?array $events = null): string
    {
        $url = rtrim((string) config('app.url'), '/')
            .'/api/v1/webhooks/evolution/'.$account->uuid;

        $events = $events ?? (array) config('whatsapp.webhook_events', ['MESSAGES_UPSERT']);

        $this->gateway->setWebhook($account->instance_name, $url, $events);

        $settings = $account->settings ?? [];
        $settings['webhook_url'] = $url;
        $settings['webhook_events'] = $events;
        $settings['webhook_configured_at'] = now()->toIso8601String();
        $account->settings = $settings;
        $account->save();

        $this->auditLogger->log('whatsapp.webhook.configured', $account, [
            'account_uuid' => $account->uuid,
            'url' => $url,
        ]);

        return $url;
    }
}
