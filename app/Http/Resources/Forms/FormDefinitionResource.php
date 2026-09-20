<?php

namespace App\Http\Resources\Forms;

use App\Models\FormDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FormDefinition
 */
class FormDefinitionResource extends JsonResource
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
            'owner_key' => $this->owner_key,
            'key' => $this->key,
            'type' => $this->type,
            'module_key' => $this->module_key,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'active_version_id' => $this->active_version_id,
            'meta' => $this->meta,
            'active_version' => $this->whenLoaded('activeVersion', fn () => new FormVersionResource($this->activeVersion)),
            'versions' => FormVersionResource::collection($this->whenLoaded('versions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
