<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Models\Appointment;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\Log;

class AppointmentReminderWhatsAppSender
{
    public function __construct(private WhatsAppGatewayInterface $gateway) {}

    public function send(Appointment $appointment): bool
    {
        if (! config('whatsapp.reminders_enabled')) {
            return false;
        }

        $patient = $appointment->patient;
        $phone = PhoneNormalizer::digits($patient?->phone);

        if ($phone === '') {
            return false;
        }

        $account = WhatsAppAccount::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $appointment->tenant_id)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if ($account === null) {
            return false;
        }

        $body = $this->resolveTemplate($appointment);

        $result = $this->gateway->sendText($account->instance_name, $phone, $body);

        if (! $result->success) {
            Log::warning('WhatsApp appointment reminder failed', [
                'appointment_uuid' => $appointment->uuid,
                'error' => $result->error,
            ]);

            return false;
        }

        // Best-effort audit row without requiring an existing conversation.
        try {
            $conversation = $account->conversations()
                ->withoutGlobalScopes()
                ->where('remote_phone', $phone)
                ->first();

            if ($conversation !== null) {
                WhatsAppMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'tenant_id' => $account->tenant_id,
                    'account_id' => $account->id,
                    'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
                    'type' => WhatsAppMessage::TYPE_TEXT,
                    'body' => $body,
                    'evolution_message_id' => $result->messageId,
                    'payload' => ['type' => 'appointment_reminder', 'appointment_uuid' => $appointment->uuid],
                    'status' => WhatsAppMessage::STATUS_SENT,
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('WhatsApp reminder message persist skipped', ['error' => $e->getMessage()]);
        }

        return true;
    }

    private function resolveTemplate(Appointment $appointment): string
    {
        $template = WhatsAppTemplate::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $appointment->tenant_id)
            ->where('key', 'appointment_reminder')
            ->where('is_active', true)
            ->first();

        $body = $template?->body ?? (string) config('whatsapp.default_reminder_template');

        return str_replace(
            ['{starts_at}', '{appointment_uuid}'],
            [
                $appointment->starts_at?->toDayDateTimeString() ?? '',
                $appointment->uuid,
            ],
            $body,
        );
    }
}
