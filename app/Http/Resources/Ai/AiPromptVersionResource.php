<?php

namespace App\Http\Resources\Ai;

use App\Models\AiPromptVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiPromptVersion
 */
class AiPromptVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prompt_id' => $this->prompt_id,
            'version' => $this->version,
            'system_prompt' => $this->system_prompt,
            'template' => $this->template,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'activated_at' => $this->activated_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
