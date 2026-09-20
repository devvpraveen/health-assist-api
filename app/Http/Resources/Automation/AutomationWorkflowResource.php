<?php

namespace App\Http\Resources\Automation;

use App\Models\AutomationWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AutomationWorkflow */
class AutomationWorkflowResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'trigger' => $this->trigger,
            'module_key' => $this->module_key,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'active_version_id' => $this->active_version_id,
            'meta' => $this->meta,
            'active_version' => $this->whenLoaded('activeVersion', fn () => new AutomationWorkflowVersionResource($this->activeVersion)),
            'versions' => AutomationWorkflowVersionResource::collection($this->whenLoaded('versions')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
