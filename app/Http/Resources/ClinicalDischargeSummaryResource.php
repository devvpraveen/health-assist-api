<?php

namespace App\Http\Resources;

use App\Models\ClinicalDischargeSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalDischargeSummary
 */
class ClinicalDischargeSummaryResource extends JsonResource
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
            'treatment_plan_id' => $this->treatment_plan_id,
            'discharged_at' => $this->discharged_at,
            'reason' => $this->reason,
            'initial_condition' => $this->initial_condition,
            'treatment_provided' => $this->treatment_provided,
            'progress_summary' => $this->progress_summary,
            'current_status' => $this->current_status,
            'home_program' => $this->home_program,
            'follow_up' => $this->follow_up,
            'referral' => $this->referral,
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
