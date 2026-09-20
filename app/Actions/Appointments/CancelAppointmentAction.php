<?php

namespace App\Actions\Appointments;

use App\Events\AppointmentCancelled;
use App\Models\Appointment;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelAppointmentAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Appointment $appointment, array $data = []): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if (in_array($appointment->status, [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_COMPLETED,
                Appointment::STATUS_RESCHEDULED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => ['This appointment cannot be cancelled.'],
                ]);
            }

            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $data['cancellation_reason'] ?? $data['reason'] ?? null,
            ]);

            $appointment->load('patient');

            $this->timelineRecorder->record(
                $appointment->patient,
                'appointment.cancelled',
                'Appointment cancelled',
                subject: $appointment,
                meta: [
                    'appointment_uuid' => $appointment->uuid,
                    'cancellation_reason' => $appointment->cancellation_reason,
                ],
            );

            $this->auditLogger->log('appointment.cancelled', $appointment, [
                'appointment_uuid' => $appointment->uuid,
            ]);

            event(new AppointmentCancelled($appointment));

            return $appointment->fresh(['patient', 'provider', 'clinic', 'reminders']);
        });
    }
}
