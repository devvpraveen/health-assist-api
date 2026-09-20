<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WhatsAppHandoff
 */
class WhatsAppHandoffResource extends JsonResource
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
            'requested_by' => $this->requested_by,
            'status' => $this->status,
            'urgency' => $this->urgency,
            'summary' => $this->summary,
            'assigned_user_id' => $this->assigned_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
