<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalDischargeSummary;
use App\Services\AuditLogger;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Support\Facades\DB;

class UpdateClinicalDischargeSummaryAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ClinicalDischargeSummary $summary, array $data): ClinicalDischargeSummary
    {
        return DB::transaction(function () use ($summary, $data): ClinicalDischargeSummary {
            ClinicalDocumentWorkflow::assertEditable($summary);

            $summary->update(collect($data)->only([
                'provider_id',
                'clinic_id',
                'treatment_plan_id',
                'discharged_at',
                'reason',
                'initial_condition',
                'treatment_provided',
                'progress_summary',
                'current_status',
                'home_program',
                'follow_up',
                'referral',
                'source',
            ])->all());

            $this->auditLogger->log('clinical.discharge.updated', $summary, [
                'discharge_uuid' => $summary->uuid,
            ]);

            return $summary->fresh();
        });
    }
}
