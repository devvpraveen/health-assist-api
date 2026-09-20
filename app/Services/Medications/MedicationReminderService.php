<?php

namespace App\Services\Medications;

use App\Jobs\SendMedicationReminderJob;
use App\Models\Medication;
use App\Models\MedicationLog;
use App\Models\MedicationReminder;
use App\Models\MedicationSchedule;
use App\Models\User;
use App\Notifications\MedicationMissedDoseNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

class MedicationReminderService
{
    /**
     * Create pending reminders for active schedules in the look-ahead window,
     * send due reminders, and mark missed doses past grace.
     *
     * @return array{created: int, sent: int, missed: int}
     */
    public function process(?CarbonImmutable $now = null, bool $syncSend = false): array
    {
        $now ??= CarbonImmutable::now('UTC');

        $created = $this->createUpcomingReminders($now);
        $sent = $this->dispatchDueReminders($now, $syncSend);
        $missed = $this->markMissedDoses($now);

        return [
            'created' => $created,
            'sent' => $sent,
            'missed' => $missed,
        ];
    }

    public function createUpcomingReminders(CarbonImmutable $now): int
    {
        $lookahead = (int) config('medication.reminder_lookahead_minutes', 120);
        $windowEnd = $now->addMinutes($lookahead);
        $channel = (string) config('medication.default_channel', MedicationReminder::CHANNEL_DATABASE);
        $created = 0;

        $schedules = MedicationSchedule::query()
            ->withoutGlobalScopes()
            ->where('is_active', true)
            ->with(['medication'])
            ->get();

        foreach ($schedules as $schedule) {
            $medication = $schedule->medication;

            if ($medication === null || $medication->status !== Medication::STATUS_ACTIVE) {
                continue;
            }

            if ($medication->end_date !== null && $medication->end_date->lt($now->toDateString())) {
                continue;
            }

            if ($medication->start_date->gt($now->toDateString())) {
                continue;
            }

            foreach ($this->occurrenceTimes($schedule, $now, $windowEnd) as $scheduledFor) {
                $exists = MedicationReminder::query()
                    ->withoutGlobalScopes()
                    ->where('medication_id', $medication->id)
                    ->where('schedule_id', $schedule->id)
                    ->where('scheduled_for', $scheduledFor->toDateTimeString())
                    ->where('channel', $channel)
                    ->exists();

                if ($exists) {
                    continue;
                }

                MedicationReminder::query()->create([
                    'tenant_id' => $medication->tenant_id,
                    'medication_id' => $medication->id,
                    'schedule_id' => $schedule->id,
                    'patient_id' => $medication->patient_id,
                    'channel' => $channel,
                    'scheduled_for' => $scheduledFor,
                    'status' => MedicationReminder::STATUS_PENDING,
                    'meta' => ['source' => 'schedule'],
                ]);

                $created++;
            }
        }

        return $created;
    }

    public function dispatchDueReminders(CarbonImmutable $now, bool $syncSend = false): int
    {
        $due = MedicationReminder::query()
            ->withoutGlobalScopes()
            ->where('status', MedicationReminder::STATUS_PENDING)
            ->where('scheduled_for', '<=', $now)
            ->orderBy('scheduled_for')
            ->limit(200)
            ->get();

        foreach ($due as $reminder) {
            if ($syncSend) {
                SendMedicationReminderJob::dispatchSync($reminder->id);
            } else {
                SendMedicationReminderJob::dispatch($reminder->id);
            }
        }

        return $due->count();
    }

    public function markMissedDoses(CarbonImmutable $now): int
    {
        $grace = (int) config('medication.missed_grace_minutes', 60);
        $cutoff = $now->subMinutes($grace);
        $marked = 0;

        $reminders = MedicationReminder::query()
            ->withoutGlobalScopes()
            ->whereIn('status', [MedicationReminder::STATUS_PENDING, MedicationReminder::STATUS_SENT])
            ->where('scheduled_for', '<=', $cutoff)
            ->with(['medication.patient.user'])
            ->limit(200)
            ->get();

        foreach ($reminders as $reminder) {
            $medication = $reminder->medication;

            if ($medication === null || $medication->status !== Medication::STATUS_ACTIVE) {
                continue;
            }

            $hasTakenOrSkipped = MedicationLog::query()
                ->withoutGlobalScopes()
                ->where('medication_id', $medication->id)
                ->where('scheduled_for', $reminder->scheduled_for)
                ->whereIn('status', [MedicationLog::STATUS_TAKEN, MedicationLog::STATUS_SKIPPED])
                ->exists();

            if ($hasTakenOrSkipped) {
                continue;
            }

            $alreadyMissed = MedicationLog::query()
                ->withoutGlobalScopes()
                ->where('medication_id', $medication->id)
                ->where('scheduled_for', $reminder->scheduled_for)
                ->where('status', MedicationLog::STATUS_MISSED)
                ->exists();

            if ($alreadyMissed) {
                continue;
            }

            MedicationLog::query()->create([
                'tenant_id' => $medication->tenant_id,
                'medication_id' => $medication->id,
                'patient_id' => $medication->patient_id,
                'schedule_id' => $reminder->schedule_id,
                'scheduled_for' => $reminder->scheduled_for,
                'logged_at' => $now,
                'status' => MedicationLog::STATUS_MISSED,
                'notes' => 'Auto-marked missed after grace period.',
                'logged_by_user_id' => null,
                'idempotency_key' => 'missed:'.$medication->id.':'.$reminder->scheduled_for->toIso8601String(),
            ]);

            $user = $medication->patient?->user;
            if ($user instanceof User) {
                try {
                    $user->notify(new MedicationMissedDoseNotification($medication, $reminder));
                } catch (Throwable $e) {
                    Log::warning('Medication missed notification failed', [
                        'medication_uuid' => $medication->uuid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($reminder->status === MedicationReminder::STATUS_PENDING) {
                $reminder->update([
                    'status' => MedicationReminder::STATUS_CANCELLED,
                    'meta' => array_merge($reminder->meta ?? [], ['cancelled_reason' => 'missed']),
                ]);
            }

            $marked++;
        }

        return $marked;
    }

    /**
     * @return list<CarbonImmutable>
     */
    private function occurrenceTimes(
        MedicationSchedule $schedule,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
    ): array {
        $tz = $schedule->timezone ?: 'UTC';
        $localStart = $windowStart->setTimezone($tz)->startOfDay()->subDay();
        $localEnd = $windowEnd->setTimezone($tz)->startOfDay()->addDays(2);
        $timeParts = explode(':', (string) $schedule->time_of_day);
        $hour = (int) ($timeParts[0] ?? 0);
        $minute = (int) ($timeParts[1] ?? 0);
        $second = (int) ($timeParts[2] ?? 0);

        /** @var list<int>|null $days */
        $days = $schedule->days_of_week;
        $occurrences = [];

        for ($day = $localStart->copy(); $day->lte($localEnd); $day = $day->addDay()) {
            if (is_array($days) && $days !== [] && ! in_array((int) $day->dayOfWeek, $days, true)) {
                continue;
            }

            $localOccurrence = $day->setTime($hour, $minute, $second);
            $utcOccurrence = $localOccurrence->setTimezone('UTC');

            if ($utcOccurrence->lt($windowStart) || $utcOccurrence->gt($windowEnd)) {
                continue;
            }

            $occurrences[] = $utcOccurrence;
        }

        return $occurrences;
    }
}
