<?php

namespace App\Actions\Appointments;

use App\Events\AppointmentRescheduled;
use App\Models\Appointment;
use App\Services\Appointments\AvailabilityService;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleAppointmentAction
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data): Appointment {
            $old = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);

            if (in_array($old->status, [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_COMPLETED,
                Appointment::STATUS_RESCHEDULED,
                Appointment::STATUS_NO_SHOW,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => ['This appointment cannot be rescheduled.'],
                ]);
            }

            $startsAt = CarbonImmutable::parse($data['starts_at']);
            $durationMinutes = (int) ($data['duration_minutes'] ?? $old->duration_minutes);
            $endsAt = isset($data['ends_at'])
                ? CarbonImmutable::parse($data['ends_at'])
                : $startsAt->addMinutes($durationMinutes);

            if ($endsAt->lte($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => ['Ends at must be after starts at.'],
                ]);
            }

            $durationMinutes = (int) $startsAt->diffInMinutes($endsAt);

            Appointment::query()
                ->busy()
                ->where('provider_id', $old->provider_id)
                ->whereKeyNot($old->id)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->lockForUpdate()
                ->get();

            if (! $this->availabilityService->isSlotAvailable(
                $old->provider_id,
                $startsAt,
                $endsAt,
                $old->id,
                isset($data['branch_id']) ? (int) $data['branch_id'] : $old->branch_id,
            )) {
                throw ValidationException::withMessages([
                    'starts_at' => ['This time slot is not available for the selected provider.'],
                ]);
            }

            $queueNumber = $this->nextQueueNumber($old->clinic_id, $startsAt);

            $new = Appointment::query()->create([
                'tenant_id' => $old->tenant_id,
                'patient_id' => $old->patient_id,
                'provider_id' => $old->provider_id,
                'clinic_id' => $old->clinic_id,
                'branch_id' => $data['branch_id'] ?? $old->branch_id,
                'service_id' => $data['service_id'] ?? $old->service_id,
                'status' => Appointment::STATUS_CONFIRMED,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $durationMinutes,
                'reason' => $data['reason'] ?? $old->reason,
                'notes' => $data['notes'] ?? $old->notes,
                'booked_by_user_id' => Auth::id() ?? $old->booked_by_user_id,
                'rescheduled_from_appointment_id' => $old->id,
                'queue_number' => $queueNumber,
                'meta' => $data['meta'] ?? $old->meta,
            ]);

            $old->update([
                'status' => Appointment::STATUS_RESCHEDULED,
            ]);

            $new->load('patient');

            $this->timelineRecorder->record(
                $new->patient,
                'appointment.rescheduled',
                'Appointment rescheduled',
                subject: $new,
                meta: [
                    'appointment_uuid' => $new->uuid,
                    'previous_appointment_uuid' => $old->uuid,
                    'starts_at' => $new->starts_at?->toIso8601String(),
                ],
            );

            $this->auditLogger->log('appointment.rescheduled', $new, [
                'appointment_uuid' => $new->uuid,
                'previous_appointment_uuid' => $old->uuid,
            ]);

            event(new AppointmentRescheduled($old->fresh(), $new));

            return $new->fresh(['patient', 'provider', 'clinic', 'service', 'reminders', 'rescheduledFrom']);
        });
    }

    private function nextQueueNumber(int $clinicId, CarbonImmutable $startsAt): int
    {
        $dayStart = $startsAt->startOfDay();
        $dayEnd = $startsAt->endOfDay();

        $max = Appointment::query()
            ->where('clinic_id', $clinicId)
            ->whereBetween('starts_at', [$dayStart, $dayEnd])
            ->whereNotNull('queue_number')
            ->lockForUpdate()
            ->max('queue_number');

        return ((int) $max) + 1;
    }
}
