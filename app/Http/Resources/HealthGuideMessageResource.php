<?php

namespace App\Http\Resources;

use App\Models\HealthGuideMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HealthGuideMessage
 */
class HealthGuideMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'conversation_id' => $this->conversation_id,
            'role' => $this->role,
            'content' => $this->content,
            'meta' => $this->meta,
            'ai_usage_record_id' => $this->ai_usage_record_id,
            'created_at' => $this->created_at,
        ];
    }
}
