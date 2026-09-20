<?php

namespace App\Jobs;

use App\Models\Medication;
use App\Models\MedicationReminder;
use App\Models\User;
use App\Notifications\MedicationReminderNotification;
use App\Services\Medications\MedicationReminderWhatsAppSender;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMedicationReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reminderId) {}

    public function handle(MedicationReminderWhatsAppSender $whatsAppSender): void
    {
        $reminder = MedicationReminder::query()
            ->withoutGlobalScopes()
            ->with(['medication.patient'])
            ->find($this->reminderId);

        if ($reminder === null || $reminder->status !== MedicationReminder::STATUS_PENDING) {
            return;
        }

        $medication = $reminder->medication;

        if ($medication === null || $medication->status !== Medication::STATUS_ACTIVE) {
            $reminder->update(['status' => MedicationReminder::STATUS_CANCELLED]);

            return;
        }

        TenantContext::set($reminder->tenant_id);

        try {
            $patient = $medication->patient;
            $user = $patient?->user_id
                ? User::query()->find($patient->user_id)
                : null;

            if ($user !== null) {
                $channels = match ($reminder->channel) {
                    MedicationReminder::CHANNEL_MAIL => ['mail'],
                    MedicationReminder::CHANNEL_LOG => ['database'],
                    default => ['database', 'mail'],
                };

                $user->notify(new MedicationReminderNotification($medication, $reminder, $channels));
            } else {
                Log::info('Health Assist medication reminder (no linked user)', [
                    'medication_uuid' => $medication->uuid,
                    'reminder_uuid' => $reminder->uuid,
                    'scheduled_for' => $reminder->scheduled_for?->toIso8601String(),
                    'channel' => $reminder->channel,
                ]);
            }

            $whatsAppSent = false;
            try {
                $whatsAppSent = $whatsAppSender->send($medication, $reminder);
            } catch (Throwable $whatsAppError) {
                Log::warning('WhatsApp medication reminder error', [
                    'medication_uuid' => $medication->uuid,
                    'error' => $whatsAppError->getMessage(),
                ]);
            }

            $reminder->update([
                'status' => MedicationReminder::STATUS_SENT,
                'sent_at' => now(),
                'meta' => array_merge($reminder->meta ?? [], [
                    'whatsapp_sent' => $whatsAppSent,
                ]),
            ]);
        } catch (Throwable $e) {
            $reminder->update([
                'status' => MedicationReminder::STATUS_FAILED,
                'meta' => array_merge($reminder->meta ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);

            throw $e;
        } finally {
            TenantContext::clear();
        }
    }
}
