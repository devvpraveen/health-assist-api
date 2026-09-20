<?php

namespace App\Actions\Medications;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateMedicationScheduleAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Medication $medication, array $data): MedicationSchedule
    {
        return DB::transaction(function () use ($medication, $data): MedicationSchedule {
            $schedule = MedicationSchedule::query()->create([
                'tenant_id' => TenantContext::id() ?? $medication->tenant_id,
                'medication_id' => $medication->id,
                'time_of_day' => $data['time_of_day'],
                'timezone' => $data['timezone'] ?? 'UTC',
                'days_of_week' => $data['days_of_week'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->auditLogger->log('medication.schedule.created', $schedule, [
                'medication_uuid' => $medication->uuid,
                'schedule_uuid' => $schedule->uuid,
            ]);

            return $schedule;
        });
    }
}
