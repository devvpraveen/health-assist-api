<?php

namespace App\Services\Medications;

use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Models\Medication;
use App\Models\MedicationReminder;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\PhoneNormalizer;
use Illuminate\Support\Facades\Log;

class MedicationReminderWhatsAppSender
{
    public function __construct(private WhatsAppGatewayInterface $gateway) {}

    public function send(Medication $medication, MedicationReminder $reminder): bool
    {
        if (! config('medication.whatsapp_reminders_enabled', true)) {
            return false;
        }

        if (! config('whatsapp.reminders_enabled')) {
            return false;
        }

        $patient = $medication->patient;
        $phone = PhoneNormalizer::digits($patient?->phone);

        if ($phone === '') {
            return false;
        }

        $account = WhatsAppAccount::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $medication->tenant_id)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if ($account === null) {
            return false;
        }

        $body = $this->resolveTemplate($medication, $reminder);

        $result = $this->gateway->sendText($account->instance_name, $phone, $body);

        if (! $result->success) {
            Log::warning('WhatsApp medication reminder failed', [
                'medication_uuid' => $medication->uuid,
                'error' => $result->error,
            ]);

            return false;
        }

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
                    'payload' => [
                        'type' => 'medication_reminder',
                        'medication_uuid' => $medication->uuid,
                        'reminder_uuid' => $reminder->uuid,
                    ],
                    'status' => WhatsAppMessage::STATUS_SENT,
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('WhatsApp medication reminder message persist skipped', ['error' => $e->getMessage()]);
        }

        return true;
    }

    private function resolveTemplate(Medication $medication, MedicationReminder $reminder): string
    {
        $template = WhatsAppTemplate::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $medication->tenant_id)
            ->where('key', 'medication_reminder')
            ->where('is_active', true)
            ->first();

        $body = $template?->body ?? (string) config('medication.default_reminder_template');

        return str_replace(
            ['{name}', '{dosage}', '{scheduled_for}', '{medication_uuid}'],
            [
                $medication->name,
                $medication->dosage,
                $reminder->scheduled_for?->toDayDateTimeString() ?? '',
                $medication->uuid,
            ],
            $body,
        );
    }
}
