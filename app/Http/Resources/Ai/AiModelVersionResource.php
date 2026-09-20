<?php

namespace App\Http\Resources\Ai;

use App\Models\AiModelVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AiModelVersion
 */
class AiModelVersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'model_id' => $this->model_id,
            'version' => $this->version,
            'is_default' => (bool) $this->is_default,
            'config' => $this->config,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
