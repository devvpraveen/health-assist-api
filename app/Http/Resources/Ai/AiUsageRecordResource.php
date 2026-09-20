<?php

namespace App\Http\Resources\Ai;

use App\Models\AiUsageRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiUsageRecord
 */
class AiUsageRecordResource extends JsonResource
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
            'feature' => $this->feature,
            'model' => $this->model,
            'model_version' => $this->model_version,
            'input_tokens' => $this->input_tokens,
            'output_tokens' => $this->output_tokens,
            'latency_ms' => $this->latency_ms,
            'status' => $this->status,
            'estimated_cost_cents' => $this->estimated_cost_cents,
            'request_id' => $this->request_id,
            'created_at' => $this->created_at,
        ];
    }
}
