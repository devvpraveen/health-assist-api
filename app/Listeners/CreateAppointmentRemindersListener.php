<?php

namespace App\Listeners;

use App\Events\AppointmentBooked;
use App\Services\Appointments\AppointmentReminderService;

class CreateAppointmentRemindersListener
{
    public function __construct(private AppointmentReminderService $reminderService) {}

    public function handle(AppointmentBooked $event): void
    {
        $this->reminderService->scheduleFor($event->appointment);
    }
}
