<?php

namespace App\Actions\Appointments;

use App\Events\AppointmentBooked;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Service;
use App\Services\Appointments\AvailabilityService;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookAppointmentAction
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Appointment
    {
        return DB::transaction(function () use ($data): Appointment {
            $provider = Provider::query()->lockForUpdate()->findOrFail($data['provider_id']);
            $patient = Patient::query()->findOrFail($data['patient_id']);
            $clinic = Clinic::query()->findOrFail($data['clinic_id'] ?? $provider->clinic_id);

            if ($patient->tenant_id !== TenantContext::id() || $provider->tenant_id !== TenantContext::id()) {
                throw ValidationException::withMessages([
                    'patient_id' => ['Patient and provider must belong to the current tenant.'],
                ]);
            }

            if ($clinic->tenant_id !== $provider->tenant_id) {
                throw ValidationException::withMessages([
                    'clinic_id' => ['Clinic must belong to the same tenant as the provider.'],
                ]);
            }

            if ($clinic->id !== $provider->clinic_id) {
                throw ValidationException::withMessages([
                    'clinic_id' => ['Clinic must match the provider clinic.'],
                ]);
            }

            $startsAt = CarbonImmutable::parse($data['starts_at']);
            $durationMinutes = (int) ($data['duration_minutes'] ?? 30);

            if (! empty($data['service_id'])) {
                $service = Service::query()->findOrFail($data['service_id']);
                if ($service->clinic_id !== $clinic->id) {
                    throw ValidationException::withMessages([
                        'service_id' => ['Service must belong to the selected clinic.'],
                    ]);
                }
                $durationMinutes = (int) ($data['duration_minutes'] ?? $service->duration_minutes);
            }

            $endsAt = isset($data['ends_at'])
                ? CarbonImmutable::parse($data['ends_at'])
                : $startsAt->addMinutes($durationMinutes);

            if ($endsAt->lte($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => ['Ends at must be after starts at.'],
                ]);
            }

            $durationMinutes = (int) $startsAt->diffInMinutes($endsAt);

            $this->assertSlotAvailable(
                $provider->id,
                $startsAt,
                $endsAt,
                branchId: isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            );

            $queueNumber = $this->nextQueueNumber($clinic->id, $startsAt);

            $status = $data['status'] ?? Appointment::STATUS_CONFIRMED;
            if (! in_array($status, [Appointment::STATUS_REQUESTED, Appointment::STATUS_CONFIRMED], true)) {
                $status = Appointment::STATUS_CONFIRMED;
            }

            $appointment = Appointment::query()->create([
                'tenant_id' => TenantContext::id(),
                'patient_id' => $patient->id,
                'provider_id' => $provider->id,
                'clinic_id' => $clinic->id,
                'branch_id' => $data['branch_id'] ?? null,
                'service_id' => $data['service_id'] ?? null,
                'status' => $status,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'duration_minutes' => $durationMinutes,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'booked_by_user_id' => Auth::id(),
                'queue_number' => $queueNumber,
                'meta' => $data['meta'] ?? null,
            ]);

            $this->timelineRecorder->record(
                $patient,
                'appointment.booked',
                'Appointment booked',
                subject: $appointment,
                meta: [
                    'appointment_uuid' => $appointment->uuid,
                    'provider_id' => $provider->id,
                    'starts_at' => $appointment->starts_at?->toIso8601String(),
                ],
            );

            $this->auditLogger->log('appointment.booked', $appointment, [
                'appointment_uuid' => $appointment->uuid,
                'patient_id' => $patient->id,
                'provider_id' => $provider->id,
            ]);

            event(new AppointmentBooked($appointment));

            return $appointment->fresh(['patient', 'provider', 'clinic', 'service', 'reminders']);
        });
    }

    private function assertSlotAvailable(
        int $providerId,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $branchId = null,
        ?int $ignoreAppointmentId = null,
    ): void {
        Appointment::query()
            ->busy()
            ->where('provider_id', $providerId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreAppointmentId !== null, fn ($query) => $query->whereKeyNot($ignoreAppointmentId))
            ->lockForUpdate()
            ->get();

        if (! $this->availabilityService->isSlotAvailable(
            $providerId,
            $startsAt,
            $endsAt,
            $ignoreAppointmentId,
            $branchId,
        )) {
            throw ValidationException::withMessages([
                'starts_at' => ['This time slot is not available for the selected provider.'],
            ]);
        }
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
