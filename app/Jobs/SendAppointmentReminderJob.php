<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Models\User;
use App\Notifications\AppointmentReminderNotification;
use App\Services\WhatsApp\AppointmentReminderWhatsAppSender;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reminderId) {}

    public function handle(AppointmentReminderWhatsAppSender $whatsAppSender): void
    {
        $reminder = AppointmentReminder::query()
            ->withoutGlobalScopes()
            ->with(['appointment.patient'])
            ->find($this->reminderId);

        if ($reminder === null || $reminder->status !== AppointmentReminder::STATUS_PENDING) {
            return;
        }

        $appointment = $reminder->appointment;

        if ($appointment === null || in_array($appointment->status, Appointment::NON_BLOCKING_STATUSES, true)) {
            $reminder->update(['status' => AppointmentReminder::STATUS_CANCELLED]);

            return;
        }

        TenantContext::set($reminder->tenant_id);

        try {
            $patient = $appointment->patient;
            $user = $patient?->user_id
                ? User::query()->find($patient->user_id)
                : null;

            if ($user !== null) {
                $channels = match ($reminder->channel) {
                    AppointmentReminder::CHANNEL_MAIL => ['mail'],
                    AppointmentReminder::CHANNEL_LOG => ['database'],
                    default => ['database', 'mail'],
                };

                $user->notify(new AppointmentReminderNotification($appointment, $reminder, $channels));
            } else {
                Log::info('Health Assist appointment reminder (no linked user)', [
                    'appointment_uuid' => $appointment->uuid,
                    'reminder_uuid' => $reminder->uuid,
                    'starts_at' => $appointment->starts_at?->toIso8601String(),
                    'channel' => $reminder->channel,
                ]);
            }

            $whatsAppSent = false;
            try {
                $whatsAppSent = $whatsAppSender->send($appointment);
            } catch (Throwable $whatsAppError) {
                Log::warning('WhatsApp appointment reminder error', [
                    'appointment_uuid' => $appointment->uuid,
                    'error' => $whatsAppError->getMessage(),
                ]);
            }

            $reminder->update([
                'status' => AppointmentReminder::STATUS_SENT,
                'sent_at' => now(),
                'meta' => array_merge($reminder->meta ?? [], [
                    'whatsapp_sent' => $whatsAppSent,
                ]),
            ]);

            $appointment->update(['reminder_sent_at' => now()]);
        } catch (Throwable $e) {
            $reminder->update([
                'status' => AppointmentReminder::STATUS_FAILED,
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
