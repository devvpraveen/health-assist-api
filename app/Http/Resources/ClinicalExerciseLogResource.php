<?php

namespace App\Http\Resources;

use App\Models\ClinicalExerciseLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalExerciseLog
 */
class ClinicalExerciseLogResource extends JsonResource
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
            'exercise_plan_item_id' => $this->exercise_plan_item_id,
            'performed_at' => $this->performed_at,
            'result' => $this->result,
            'notes' => $this->notes,
            'pain_score' => $this->pain_score,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
