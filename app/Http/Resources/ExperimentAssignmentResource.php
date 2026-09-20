<?php

namespace App\Http\Resources;

use App\Models\ExperimentAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExperimentAssignment */
class ExperimentAssignmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'experiment_id' => $this->experiment_id,
            'anonymous_id' => $this->anonymous_id,
            'variant_key' => $this->variant_key,
            'assigned_at' => $this->assigned_at,
            'experiment_key' => $this->whenLoaded('experiment', fn () => $this->experiment?->key),
        ];
    }
}
