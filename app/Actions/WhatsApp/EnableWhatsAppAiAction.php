<?php

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Services\AuditLogger;

class EnableWhatsAppAiAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(WhatsAppConversation $conversation): WhatsAppConversation
    {
        $conversation->state = WhatsAppConversation::STATE_AI;
        $conversation->save();

        $this->auditLogger->log('whatsapp.conversation.ai_enabled', $conversation, [
            'conversation_uuid' => $conversation->uuid,
        ]);

        return $conversation->fresh();
    }
}
