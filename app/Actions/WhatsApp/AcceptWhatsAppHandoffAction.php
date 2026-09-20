<?php

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppHandoff;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AcceptWhatsAppHandoffAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(WhatsAppConversation $conversation, ?WhatsAppHandoff $handoff = null): WhatsAppHandoff
    {
        $handoff ??= WhatsAppHandoff::query()
            ->where('conversation_id', $conversation->id)
            ->where('status', WhatsAppHandoff::STATUS_OPEN)
            ->latest('id')
            ->first();

        if ($handoff === null) {
            throw ValidationException::withMessages([
                'handoff' => ['No open handoff found for this conversation.'],
            ]);
        }

        $handoff->status = WhatsAppHandoff::STATUS_ACCEPTED;
        $handoff->assigned_user_id = Auth::id();
        $handoff->save();

        $conversation->state = WhatsAppConversation::STATE_HUMAN;
        $conversation->save();

        $this->auditLogger->log('whatsapp.handoff.accepted', $handoff, [
            'conversation_uuid' => $conversation->uuid,
            'handoff_uuid' => $handoff->uuid,
        ]);

        return $handoff->fresh();
    }
}
