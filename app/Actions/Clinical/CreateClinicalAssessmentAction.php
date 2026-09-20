<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalAssessment;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Forms\FormEngine;
use App\Services\Patients\PatientTimelineRecorder;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateClinicalAssessmentAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private FormEngine $formEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Patient $patient, array $data): ClinicalAssessment
    {
        $findings = $data['findings'] ?? null;
        $templateKey = $data['template_key'] ?? null;

        if (is_string($templateKey) && $templateKey !== '' && is_array($findings)) {
            $tenantId = (int) (TenantContext::id() ?? $patient->tenant_id);
            $form = $this->formEngine->resolvePublished($templateKey, $tenantId, 'assessment');
            if ($form?->activeVersion !== null) {
                $findings = $this->formEngine->validate($form->activeVersion, $findings);
            }
        }

        return DB::transaction(function () use ($patient, $data, $findings, $templateKey): ClinicalAssessment {
            $assessment = ClinicalAssessment::query()->create([
                'tenant_id' => TenantContext::id() ?? $patient->tenant_id,
                'patient_id' => $patient->id,
                'provider_id' => $data['provider_id'] ?? null,
                'clinic_id' => $data['clinic_id'] ?? null,
                'appointment_id' => $data['appointment_id'] ?? null,
                'template_key' => $templateKey,
                'assessed_at' => $data['assessed_at'],
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'findings' => $findings,
                'summary' => $data['summary'] ?? null,
                'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
                'source' => $data['source'] ?? ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
                'authored_by_user_id' => Auth::id(),
            ]);

            $this->timelineRecorder->record(
                $patient,
                'clinical.assessment.created',
                'Clinical assessment created',
                subject: $assessment,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'assessment_uuid' => $assessment->uuid,
                ],
            );

            $this->auditLogger->log('clinical.assessment.created', $assessment, [
                'patient_uuid' => $patient->uuid,
                'assessment_uuid' => $assessment->uuid,
            ]);

            return $assessment;
        });
    }
}
