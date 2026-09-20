<?php

namespace App\Listeners;

use App\Events\AppointmentCancelled;
use App\Services\Appointments\AppointmentReminderService;

class CancelAppointmentRemindersListener
{
    public function __construct(private AppointmentReminderService $reminderService) {}

    public function handle(AppointmentCancelled $event): void
    {
        $this->reminderService->cancelPending($event->appointment);
    }
}
