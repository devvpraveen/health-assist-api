<?php

namespace App\Http\Resources;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Exercise
 */
class ExerciseResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'instructions' => $this->instructions,
            'contraindications' => $this->contraindications,
            'default_duration_seconds' => $this->default_duration_seconds,
            'default_sets' => $this->default_sets,
            'default_reps' => $this->default_reps,
            'difficulty' => $this->difficulty,
            'media_url' => $this->media_url,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
