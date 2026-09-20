<?php

namespace App\Actions\Appointments;

use App\Events\AppointmentCompleted;
use App\Models\Appointment;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentStatusAction
{
    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        Appointment::STATUS_CHECKED_IN => [
            Appointment::STATUS_IN_CONSULTATION,
            Appointment::STATUS_NO_SHOW,
            Appointment::STATUS_COMPLETED,
        ],
        Appointment::STATUS_IN_CONSULTATION => [
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_NO_SHOW,
        ],
        Appointment::STATUS_CONFIRMED => [
            Appointment::STATUS_NO_SHOW,
            Appointment::STATUS_CHECKED_IN,
        ],
        Appointment::STATUS_REQUESTED => [
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_NO_SHOW,
            Appointment::STATUS_CANCELLED,
        ],
    ];

    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array{status: string}  $data
     */
    public function handle(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $newStatus = $data['status'];

            $allowed = self::TRANSITIONS[$appointment->status] ?? [];

            if (! in_array($newStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => ["Cannot transition from {$appointment->status} to {$newStatus}."],
                ]);
            }

            $payload = ['status' => $newStatus];

            if ($newStatus === Appointment::STATUS_CHECKED_IN && $appointment->checked_in_at === null) {
                $payload['checked_in_at'] = now();
            }

            $previous = $appointment->status;
            $appointment->update($payload);
            $appointment->load('patient');

            $this->timelineRecorder->record(
                $appointment->patient,
                'appointment.status_updated',
                'Appointment status updated',
                subject: $appointment,
                meta: [
                    'appointment_uuid' => $appointment->uuid,
                    'from' => $previous,
                    'to' => $newStatus,
                ],
            );

            $this->auditLogger->log('appointment.status_updated', $appointment, [
                'appointment_uuid' => $appointment->uuid,
                'from' => $previous,
                'to' => $newStatus,
            ]);

            if ($newStatus === Appointment::STATUS_COMPLETED) {
                event(new AppointmentCompleted($appointment));
            }

            return $appointment->fresh(['patient', 'provider', 'clinic']);
        });
    }
}
