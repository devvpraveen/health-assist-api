<?php

namespace App\Http\Resources\Ai;

use App\Models\AiAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiAuditLog
 */
class AiAuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'agent' => $this->agent,
            'model' => $this->model,
            'model_version' => $this->model_version,
            'prompt_key' => $this->prompt_key,
            'prompt_version' => $this->prompt_version,
            'input_type' => $this->input_type,
            'input_redacted' => $this->input_redacted,
            'output_redacted' => $this->output_redacted,
            'review_status' => $this->review_status,
            'reviewer_user_id' => $this->reviewer_user_id,
            'usage_record_id' => $this->usage_record_id,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
        ];
    }
}
