<?php

namespace App\Notifications;

use App\Models\Medication;
use App\Models\MedicationReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicationMissedDoseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Medication $medication,
        public MedicationReminder $reminder,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Health Assist missed medication dose')
            ->line('A scheduled dose was marked as missed.')
            ->line('Medication: '.$this->medication->name.' ('.$this->medication->dosage.')')
            ->line('Scheduled for: '.$this->reminder->scheduled_for?->toDayDateTimeString())
            ->line('If you took it late, please log it as taken.')
            ->line((string) config('medication.disclaimer'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'medication_missed',
            'medication_uuid' => $this->medication->uuid,
            'reminder_uuid' => $this->reminder->uuid,
            'name' => $this->medication->name,
            'dosage' => $this->medication->dosage,
            'scheduled_for' => $this->reminder->scheduled_for?->toIso8601String(),
            'disclaimer' => config('medication.disclaimer'),
        ];
    }
}
