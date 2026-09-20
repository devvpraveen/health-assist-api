<?php

namespace App\Notifications;

use App\Models\Medication;
use App\Models\MedicationReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public Medication $medication,
        public MedicationReminder $reminder,
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
            ->subject('Health Assist medication reminder')
            ->line('Time to take your medication.')
            ->line('Medication: '.$this->medication->name.' ('.$this->medication->dosage.')')
            ->line('Scheduled for: '.$this->reminder->scheduled_for?->toDayDateTimeString())
            ->line((string) config('medication.disclaimer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'medication_reminder',
            'medication_uuid' => $this->medication->uuid,
            'reminder_uuid' => $this->reminder->uuid,
            'name' => $this->medication->name,
            'dosage' => $this->medication->dosage,
            'scheduled_for' => $this->reminder->scheduled_for?->toIso8601String(),
            'disclaimer' => config('medication.disclaimer'),
        ];
    }
}
