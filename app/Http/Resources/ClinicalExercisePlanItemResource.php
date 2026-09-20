<?php

namespace App\Http\Resources;

use App\Models\ClinicalExercisePlanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClinicalExercisePlanItem
 */
class ClinicalExercisePlanItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exercise_plan_id' => $this->exercise_plan_id,
            'exercise_id' => $this->exercise_id,
            'custom_name' => $this->custom_name,
            'frequency' => $this->frequency,
            'sets' => $this->sets,
            'reps' => $this->reps,
            'duration_seconds' => $this->duration_seconds,
            'instructions' => $this->instructions,
            'sort_order' => $this->sort_order,
            'exercise' => ExerciseResource::make($this->whenLoaded('exercise')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
