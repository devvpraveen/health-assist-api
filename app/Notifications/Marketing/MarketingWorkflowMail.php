<?php

namespace App\Notifications\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MarketingWorkflowMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public string $unsubscribeToken,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unsubscribeUrl = url('/api/v1/public/email/unsubscribe?token='.$this->unsubscribeToken);

        return (new MailMessage)
            ->subject($this->subjectLine)
            ->line($this->bodyText)
            ->line('To unsubscribe, visit: '.$unsubscribeUrl);
    }
}
