<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WhatsAppMessage
 */
class WhatsAppMessageResource extends JsonResource
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
            'direction' => $this->direction,
            'type' => $this->type,
            'body' => $this->body,
            'evolution_message_id' => $this->evolution_message_id,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
