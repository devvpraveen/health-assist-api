<?php

namespace App\Modules\Definitions;

class AppointmentsModule extends AbstractModuleDefinition
{
    public function key(): string
    {
        return 'appointments';
    }

    public function definition(): array
    {
        return [
            'name' => 'Appointments',
            'description' => 'Booking, queue, reminders, and availability.',
            'category' => 'operations',
        ];
    }

    public function dependencies(): array
    {
        return ['core', 'patients', 'providers'];
    }

    public function capabilities(): array
    {
        return ['appointments.view', 'appointments.manage', 'appointments.book'];
    }

    public function permissions(): array
    {
        return ['appointments.view', 'appointments.manage', 'appointments.queue.manage'];
    }

    public function navigation(): array
    {
        return ['appointments', 'dashboard'];
    }
}
