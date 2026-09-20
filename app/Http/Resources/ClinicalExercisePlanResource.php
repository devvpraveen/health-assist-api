<?php

namespace App\Http\Resources;

use App\Models\ClinicalExercisePlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalExercisePlan
 */
class ClinicalExercisePlanResource extends JsonResource
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
            'provider_id' => $this->provider_id,
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => ClinicalExercisePlanItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
