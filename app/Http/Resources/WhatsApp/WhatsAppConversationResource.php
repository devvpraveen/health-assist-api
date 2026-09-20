<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WhatsAppConversation
 */
class WhatsAppConversationResource extends JsonResource
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
            'account_id' => $this->account_id,
            'patient_id' => $this->patient_id,
            'remote_phone' => $this->remote_phone,
            'remote_jid' => $this->remote_jid,
            'state' => $this->state,
            'health_guide_conversation_id' => $this->health_guide_conversation_id,
            'handoff_summary' => $this->handoff_summary,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'account' => new WhatsAppAccountResource($this->whenLoaded('account')),
            'messages' => WhatsAppMessageResource::collection($this->whenLoaded('messages')),
            'handoffs' => WhatsAppHandoffResource::collection($this->whenLoaded('handoffs')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
