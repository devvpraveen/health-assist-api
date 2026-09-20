<?php

namespace App\Http\Resources;

use App\Models\ClinicalProgressNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalProgressNote
 */
class ClinicalProgressNoteResource extends JsonResource
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
            'treatment_plan_id' => $this->treatment_plan_id,
            'appointment_id' => $this->appointment_id,
            'noted_at' => $this->noted_at,
            'note' => $this->note,
            'measurements' => $this->measurements,
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
