<?php

namespace App\Http\Resources;

use App\Models\PatientHealthProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PatientHealthProfile
 */
class PatientHealthProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'patient_id' => $this->patient_id,
            'medical_history' => $this->medical_history,
            'conditions' => $this->conditions,
            'allergies' => $this->allergies,
            'medications' => $this->medications,
            'previous_treatments' => $this->previous_treatments,
            'surgeries' => $this->surgeries,
            'family_history' => $this->family_history,
            'lifestyle' => $this->lifestyle,
            'emergency_information' => $this->emergency_information,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
