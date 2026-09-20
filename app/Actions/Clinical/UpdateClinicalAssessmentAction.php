<?php

namespace App\Actions\Clinical;

use App\Models\ClinicalAssessment;
use App\Services\AuditLogger;
use App\Support\ClinicalDocumentWorkflow;
use Illuminate\Support\Facades\DB;

class UpdateClinicalAssessmentAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(ClinicalAssessment $assessment, array $data): ClinicalAssessment
    {
        return DB::transaction(function () use ($assessment, $data): ClinicalAssessment {
            ClinicalDocumentWorkflow::assertEditable($assessment);

            $assessment->update(collect($data)->only([
                'provider_id',
                'clinic_id',
                'appointment_id',
                'template_key',
                'assessed_at',
                'chief_complaint',
                'findings',
                'summary',
                'source',
            ])->all());

            $this->auditLogger->log('clinical.assessment.updated', $assessment, [
                'assessment_uuid' => $assessment->uuid,
            ]);

            return $assessment->fresh();
        });
    }
}
