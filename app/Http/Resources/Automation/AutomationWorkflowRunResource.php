<?php

namespace App\Http\Resources\Automation;

use App\Models\AutomationWorkflowRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AutomationWorkflowRun */
class AutomationWorkflowRunResource extends JsonResource
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
            'workflow_version_id' => $this->workflow_version_id,
            'trigger' => $this->trigger,
            'status' => $this->status,
            'current_step' => $this->current_step,
            'context' => $this->context,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'step_logs' => AutomationWorkflowRunStepResource::collection($this->whenLoaded('steps')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
