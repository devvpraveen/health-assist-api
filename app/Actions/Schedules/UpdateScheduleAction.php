<?php

namespace App\Actions\Schedules;

use App\Models\Schedule;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateScheduleAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Schedule $schedule, array $data): Schedule
    {
        return DB::transaction(function () use ($schedule, $data): Schedule {
            $schedule->update($data);

            $this->auditLogger->log('schedule.updated', $schedule, [
                'schedule_uuid' => $schedule->uuid,
                'fields' => array_keys($data),
            ]);

            return $schedule->refresh();
        });
    }
}
