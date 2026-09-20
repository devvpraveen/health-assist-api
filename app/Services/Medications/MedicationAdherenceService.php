<?php

namespace App\Services\Medications;

use App\Models\Medication;
use App\Models\MedicationLog;

class MedicationAdherenceService
{
    /**
     * @return array{taken: int, missed: int, skipped: int, total: int, adherence_percent: float|null}
     */
    public function summarize(Medication $medication): array
    {
        $counts = MedicationLog::query()
            ->where('medication_id', $medication->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $taken = (int) ($counts[MedicationLog::STATUS_TAKEN] ?? 0);
        $missed = (int) ($counts[MedicationLog::STATUS_MISSED] ?? 0);
        $skipped = (int) ($counts[MedicationLog::STATUS_SKIPPED] ?? 0);
        $total = $taken + $missed + $skipped;
        $denominator = $taken + $missed;

        return [
            'taken' => $taken,
            'missed' => $missed,
            'skipped' => $skipped,
            'total' => $total,
            'adherence_percent' => $denominator > 0
                ? round(($taken / $denominator) * 100, 1)
                : null,
        ];
    }
}
