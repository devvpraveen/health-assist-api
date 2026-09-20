<?php

namespace App\Actions\Clinical;

use App\Events\DischargeCreated;
use App\Models\ClinicalDischargeSummary;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateClinicalDischargeSummaryAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): ClinicalDischargeSummary
    {
        return DB::transaction(function () use ($patient, $data): ClinicalDischargeSummary {
            $summary = ClinicalDischargeSummary::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'provider_id' => $data['provider_id'] ?? null,
                'clinic_id' => $data['clinic_id'] ?? null,
                'treatment_plan_id' => $data['treatment_plan_id'] ?? null,
                'discharged_at' => $data['discharged_at'],
                'reason' => $data['reason'] ?? null,
                'initial_condition' => $data['initial_condition'] ?? null,
                'treatment_provided' => $data['treatment_provided'] ?? null,
                'progress_summary' => $data['progress_summary'] ?? null,
                'current_status' => $data['current_status'] ?? null,
                'home_program' => $data['home_program'] ?? null,
                'follow_up' => $data['follow_up'] ?? null,
                'referral' => $data['referral'] ?? null,
                'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
                'source' => $data['source'] ?? ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
                'authored_by_user_id' => Auth::id(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'clinical.discharge.created',
                'Discharge summary created',
                subject: $summary,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'discharge_uuid' => $summary->uuid,
                ],
            );

            $this->auditLogger->log('clinical.discharge.created', $summary, [
                'patient_uuid' => $patient->uuid,
                'discharge_uuid' => $summary->uuid,
            ]);

            event(new DischargeCreated($summary));

            return $summary;
        });
    }
}
