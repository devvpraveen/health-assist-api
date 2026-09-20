<?php

namespace App\Actions\Appointments;

use App\Models\Appointment;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInAppointmentAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    public function handle(Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($appointment): Appointment {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if (! in_array($appointment->status, [
                Appointment::STATUS_REQUESTED,
                Appointment::STATUS_CONFIRMED,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => ['Only requested or confirmed appointments can be checked in.'],
                ]);
            }

            $appointment->update([
                'status' => Appointment::STATUS_CHECKED_IN,
                'checked_in_at' => now(),
            ]);

            $appointment->load('patient');

            $this->timelineRecorder->record(
                $appointment->patient,
                'appointment.checked_in',
                'Patient checked in',
                subject: $appointment,
                meta: ['appointment_uuid' => $appointment->uuid],
            );

            $this->auditLogger->log('appointment.checked_in', $appointment, [
                'appointment_uuid' => $appointment->uuid,
            ]);

            return $appointment->fresh(['patient', 'provider', 'clinic']);
        });
    }
}
