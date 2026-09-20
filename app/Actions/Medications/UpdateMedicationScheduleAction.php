<?php

namespace App\Actions\Medications;

use App\Models\MedicationSchedule;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateMedicationScheduleAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(MedicationSchedule $schedule, array $data): MedicationSchedule
    {
        return DB::transaction(function () use ($schedule, $data): MedicationSchedule {
            $schedule->update($data);

            $this->auditLogger->log('medication.schedule.updated', $schedule, [
                'schedule_uuid' => $schedule->uuid,
                'medication_uuid' => $schedule->medication?->uuid,
            ]);

            return $schedule->refresh();
        });
    }
}
