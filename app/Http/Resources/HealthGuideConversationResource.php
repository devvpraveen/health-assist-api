<?php

namespace App\Http\Resources;

use App\Models\HealthGuideConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HealthGuideConversation
 */
class HealthGuideConversationResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'locale' => $this->locale,
            'structured_state' => $this->structured_state,
            'safety_level' => $this->safety_level,
            'last_message_at' => $this->last_message_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages' => HealthGuideMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
