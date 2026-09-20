<?php

namespace App\Http\Resources;

use App\Models\ClinicalSoapNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalSoapNote
 */
class ClinicalSoapNoteResource extends JsonResource
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
            'assessment_id' => $this->assessment_id,
            'subjective' => $this->subjective,
            'objective' => $this->objective,
            'assessment' => $this->assessment,
            'plan' => $this->plan,
            'session_date' => $this->session_date,
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
