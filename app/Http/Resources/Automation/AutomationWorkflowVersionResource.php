<?php

namespace App\Http\Resources\Automation;

use App\Models\AutomationWorkflowVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AutomationWorkflowVersion */
class AutomationWorkflowVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'workflow_id' => $this->workflow_id,
            'version' => $this->version,
            'status' => $this->status,
            'label' => $this->label,
            'steps' => $this->steps,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
