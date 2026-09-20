<?php

namespace App\Http\Resources\Automation;

use App\Models\AutomationWorkflowRunStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AutomationWorkflowRunStep */
class AutomationWorkflowRunStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'step_index' => $this->step_index,
            'step_type' => $this->step_type,
            'status' => $this->status,
            'input' => $this->input,
            'output' => $this->output,
            'error_message' => $this->error_message,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
        ];
    }
}
