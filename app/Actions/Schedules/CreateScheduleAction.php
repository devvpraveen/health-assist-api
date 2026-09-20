<?php

namespace App\Actions\Schedules;

use App\Models\Schedule;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateScheduleAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Schedule
    {
        return DB::transaction(function () use ($data): Schedule {
            $schedule = Schedule::query()->create([
                ...$data,
                'tenant_id' => TenantContext::id(),
            ]);

            $this->auditLogger->log('schedule.created', $schedule, [
                'schedule_uuid' => $schedule->uuid,
                'provider_id' => $schedule->provider_id,
                'day_of_week' => $schedule->day_of_week,
            ]);

            return $schedule;
        });
    }
}
