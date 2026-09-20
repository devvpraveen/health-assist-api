<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentReminder;
use Illuminate\Support\Carbon;

class AppointmentReminderService
{
    public function scheduleFor(Appointment $appointment, string $channel = AppointmentReminder::CHANNEL_DATABASE): void
    {
        $this->cancelPending($appointment);

        $now = now();
        $startsAt = Carbon::parse($appointment->starts_at);

        $offsets = [
            ['hours' => 24, 'key' => '24h'],
            ['hours' => 2, 'key' => '2h'],
        ];

        foreach ($offsets as $offset) {
            $scheduledFor = $startsAt->copy()->subHours($offset['hours']);

            if ($scheduledFor->lte($now)) {
                continue;
            }

            AppointmentReminder::query()->create([
                'tenant_id' => $appointment->tenant_id,
                'appointment_id' => $appointment->id,
                'channel' => $channel,
                'scheduled_for' => $scheduledFor,
                'status' => AppointmentReminder::STATUS_PENDING,
                'meta' => ['offset' => $offset['key']],
            ]);
        }
    }

    public function cancelPending(Appointment $appointment): void
    {
        AppointmentReminder::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', AppointmentReminder::STATUS_PENDING)
            ->update(['status' => AppointmentReminder::STATUS_CANCELLED]);
    }
}
