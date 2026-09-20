<?php

namespace App\Http\Resources;

use App\Models\ClinicalTreatmentPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalTreatmentPlan
 */
class ClinicalTreatmentPlanResource extends JsonResource
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
            'provider_id' => $this->provider_id,
            'clinic_id' => $this->clinic_id,
            'title' => $this->title,
            'diagnosis_summary' => $this->diagnosis_summary,
            'goals' => $this->goals,
            'frequency' => $this->frequency,
            'duration_weeks' => $this->duration_weeks,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'reassessment_date' => $this->reassessment_date,
            'home_program_notes' => $this->home_program_notes,
            'status' => $this->status,
            'source' => $this->source,
            'authored_by_user_id' => $this->authored_by_user_id,
            'approved_by_user_id' => $this->approved_by_user_id,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
