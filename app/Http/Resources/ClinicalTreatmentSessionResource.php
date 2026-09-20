<?php

namespace App\Http\Resources;

use App\Models\ClinicalTreatmentSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalTreatmentSession
 */
class ClinicalTreatmentSessionResource extends JsonResource
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
            'treatment_plan_id' => $this->treatment_plan_id,
            'appointment_id' => $this->appointment_id,
            'provider_id' => $this->provider_id,
            'session_at' => $this->session_at,
            'modality' => $this->modality,
            'interventions' => $this->interventions,
            'patient_response' => $this->patient_response,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
