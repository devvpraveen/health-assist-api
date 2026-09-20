<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public Appointment $appointment,
        public AppointmentReminder $reminder,
        public array $channels = ['database', 'mail'],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return array_values(array_filter(
            $this->channels,
            fn (string $channel): bool => in_array($channel, ['mail', 'database'], true)
        )) ?: ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Health Assist appointment reminder')
            ->line('You have an upcoming appointment.')
            ->line('Starts at: '.$this->appointment->starts_at?->toDayDateTimeString())
            ->line('Please arrive a few minutes early.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appointment_uuid' => $this->appointment->uuid,
            'reminder_uuid' => $this->reminder->uuid,
            'starts_at' => $this->appointment->starts_at?->toIso8601String(),
            'ends_at' => $this->appointment->ends_at?->toIso8601String(),
            'clinic_id' => $this->appointment->clinic_id,
            'provider_id' => $this->appointment->provider_id,
        ];
    }
}
