<?php

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppHandoff;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class ResolveWhatsAppHandoffAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(WhatsAppConversation $conversation, ?WhatsAppHandoff $handoff = null): WhatsAppHandoff
    {
        $handoff ??= WhatsAppHandoff::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('status', [WhatsAppHandoff::STATUS_OPEN, WhatsAppHandoff::STATUS_ACCEPTED])
            ->latest('id')
            ->first();

        if ($handoff === null) {
            throw ValidationException::withMessages([
                'handoff' => ['No open handoff found for this conversation.'],
            ]);
        }

        $handoff->status = WhatsAppHandoff::STATUS_RESOLVED;
        $handoff->save();

        $this->auditLogger->log('whatsapp.handoff.resolved', $handoff, [
            'conversation_uuid' => $conversation->uuid,
            'handoff_uuid' => $handoff->uuid,
        ]);

        return $handoff->fresh();
    }
}
