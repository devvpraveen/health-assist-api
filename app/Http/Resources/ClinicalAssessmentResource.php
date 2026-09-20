<?php

namespace App\Http\Resources;

use App\Models\ClinicalAssessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalAssessment
 */
class ClinicalAssessmentResource extends JsonResource
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
            'appointment_id' => $this->appointment_id,
            'template_key' => $this->template_key,
            'assessed_at' => $this->assessed_at,
            'chief_complaint' => $this->chief_complaint,
            'findings' => $this->findings,
            'summary' => $this->summary,
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
