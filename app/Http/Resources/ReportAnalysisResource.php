<?php

namespace App\Http\Resources;

use App\Models\ReportAnalysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReportAnalysis
 */
class ReportAnalysisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'patient_id' => $this->patient_id,
            'document_id' => $this->document_id,
            'health_record_id' => $this->health_record_id,
            'status' => $this->status,
            'report_type' => $this->report_type,
            'ocr_provider' => $this->ocr_provider,
            'extracted_facts' => $this->extracted_facts,
            'interpretation' => $this->interpretation,
            'reference_range_findings' => $this->reference_range_findings,
            'safety_level' => $this->safety_level,
            'safety_assessment_id' => $this->safety_assessment_id,
            'patient_explanation' => $this->patient_explanation,
            'clinician_notes' => $this->clinician_notes,
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'reviewed_at' => $this->reviewed_at,
            'ai_usage_record_id' => $this->ai_usage_record_id,
            'error_message' => $this->error_message,
            'requires_clinician_review' => true,
            'disclaimer' => config('report_ai.disclaimer'),
            'versions' => ReportAnalysisVersionResource::collection($this->whenLoaded('versions')),
            'document' => new PatientDocumentResource($this->whenLoaded('document')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
