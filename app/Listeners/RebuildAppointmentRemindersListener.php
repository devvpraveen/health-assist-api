<?php

namespace App\Listeners;

use App\Events\AppointmentRescheduled;
use App\Services\Appointments\AppointmentReminderService;

class RebuildAppointmentRemindersListener
{
    public function __construct(private AppointmentReminderService $reminderService) {}

    public function handle(AppointmentRescheduled $event): void
    {
        $this->reminderService->cancelPending($event->previous);
        $this->reminderService->scheduleFor($event->appointment);
    }
}
