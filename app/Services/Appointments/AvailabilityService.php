<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    public const MAX_DAYS = 14;

    /**
     * @return list<array{starts_at: string, ends_at: string}>
     */
    public function slots(
        int $providerId,
        Carbon|CarbonImmutable|string $date,
        int $days = 1,
        ?int $serviceId = null,
        ?int $branchId = null,
        ?int $clinicId = null,
    ): array {
        if ($days < 1 || $days > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                'days' => ['Days must be between 1 and '.self::MAX_DAYS.'.'],
            ]);
        }

        $provider = Provider::query()->findOrFail($providerId);
        $startDate = CarbonImmutable::parse($date)->startOfDay();
        $endDate = $startDate->addDays($days - 1)->endOfDay();

        $serviceDuration = $serviceId !== null
            ? $this->resolveServiceDuration($provider, $serviceId)
            : null;

        $schedulesQuery = Schedule::query()
            ->where('provider_id', $provider->id)
            ->where('is_active', true);

        if ($branchId !== null) {
            $schedulesQuery->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        }

        if ($clinicId !== null) {
            $schedulesQuery->where('clinic_id', $clinicId);
        }

        /** @var Collection<int, Collection<int, Schedule>> $schedules */
        $schedules = $schedulesQuery->get()->groupBy('day_of_week');

        $busyAppointments = Appointment::query()
            ->busy()
            ->where('provider_id', $provider->id)
            ->where('starts_at', '<', $endDate)
            ->where('ends_at', '>', $startDate)
            ->get(['starts_at', 'ends_at']);

        $slots = [];

        for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
            $day = $startDate->addDays($dayOffset);
            $daySchedules = $schedules->get($day->dayOfWeek, collect());

            foreach ($daySchedules as $schedule) {
                $durationMinutes = $serviceDuration ?? (int) $schedule->slot_duration_minutes;
                $windowStart = $day->setTimeFromTimeString(
                    $this->normalizeTime((string) $schedule->start_time)
                );
                $windowEnd = $day->setTimeFromTimeString(
                    $this->normalizeTime((string) $schedule->end_time)
                );

                $slotStart = $windowStart;

                while (true) {
                    $slotEnd = $slotStart->addMinutes($durationMinutes);

                    if ($slotEnd->gt($windowEnd)) {
                        break;
                    }

                    if (! $this->overlapsBusy($slotStart, $slotEnd, $busyAppointments)) {
                        $slots[] = [
                            'starts_at' => $slotStart->toIso8601String(),
                            'ends_at' => $slotEnd->toIso8601String(),
                        ];
                    }

                    $slotStart = $slotStart->addMinutes($durationMinutes);
                }
            }
        }

        return $slots;
    }

    public function isSlotAvailable(
        int $providerId,
        Carbon|CarbonImmutable|string $startsAt,
        Carbon|CarbonImmutable|string $endsAt,
        ?int $ignoreAppointmentId = null,
        ?int $branchId = null,
    ): bool {
        $starts = CarbonImmutable::parse($startsAt);
        $ends = CarbonImmutable::parse($endsAt);

        if ($ends->lte($starts)) {
            return false;
        }

        if (! $this->fitsActiveSchedule($providerId, $starts, $ends, $branchId)) {
            return false;
        }

        $query = Appointment::query()
            ->busy()
            ->where('provider_id', $providerId)
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts);

        if ($ignoreAppointmentId !== null) {
            $query->whereKeyNot($ignoreAppointmentId);
        }

        return ! $query->exists();
    }

    public function fitsActiveSchedule(
        int $providerId,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $branchId = null,
    ): bool {
        $schedulesQuery = Schedule::query()
            ->where('provider_id', $providerId)
            ->where('is_active', true)
            ->where('day_of_week', $startsAt->dayOfWeek);

        if ($branchId !== null) {
            $schedulesQuery->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        }

        $schedules = $schedulesQuery->get();

        if ($schedules->isEmpty()) {
            return false;
        }

        $day = $startsAt->startOfDay();

        foreach ($schedules as $schedule) {
            $windowStart = $day->setTimeFromTimeString(
                $this->normalizeTime((string) $schedule->start_time)
            );
            $windowEnd = $day->setTimeFromTimeString(
                $this->normalizeTime((string) $schedule->end_time)
            );

            if ($startsAt->gte($windowStart) && $endsAt->lte($windowEnd)) {
                return true;
            }
        }

        return false;
    }

    private function resolveServiceDuration(Provider $provider, int $serviceId): int
    {
        $service = Service::query()
            ->whereKey($serviceId)
            ->where('clinic_id', $provider->clinic_id)
            ->firstOrFail();

        return (int) $service->duration_minutes;
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    /**
     * @param  Collection<int, Appointment>  $busyAppointments
     */
    private function overlapsBusy(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        Collection $busyAppointments,
    ): bool {
        foreach ($busyAppointments as $appointment) {
            $busyStart = CarbonImmutable::parse($appointment->starts_at);
            $busyEnd = CarbonImmutable::parse($appointment->ends_at);

            if ($startsAt->lt($busyEnd) && $endsAt->gt($busyStart)) {
                return true;
            }
        }

        return false;
    }
}
