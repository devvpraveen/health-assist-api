<?php

namespace App\Http\Resources;

use App\Models\Experiment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Experiment */
class ExperimentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'tenant_key' => $this->tenant_key,
            'key' => $this->key,
            'name' => $this->name,
            'status' => $this->status,
            'variants' => ExperimentVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
