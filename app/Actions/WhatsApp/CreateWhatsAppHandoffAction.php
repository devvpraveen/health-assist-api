<?php

namespace App\Actions\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppHandoff;
use App\Services\AuditLogger;

class CreateWhatsAppHandoffAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(WhatsAppConversation $conversation, array $data = []): WhatsAppHandoff
    {
        $open = WhatsAppHandoff::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('status', [WhatsAppHandoff::STATUS_OPEN, WhatsAppHandoff::STATUS_ACCEPTED])
            ->first();

        if ($open !== null) {
            return $open;
        }

        $handoff = WhatsAppHandoff::query()->create([
            'tenant_id' => $conversation->tenant_id,
            'conversation_id' => $conversation->id,
            'requested_by' => $data['requested_by'] ?? WhatsAppHandoff::REQUESTED_BY_STAFF,
            'status' => WhatsAppHandoff::STATUS_OPEN,
            'urgency' => $data['urgency'] ?? null,
            'summary' => $data['summary'] ?? $conversation->handoff_summary,
        ]);

        $conversation->state = WhatsAppConversation::STATE_WAITING_HUMAN;
        if (! empty($data['summary'])) {
            $conversation->handoff_summary = $data['summary'];
        }
        $conversation->save();

        $this->auditLogger->log('whatsapp.handoff.created', $handoff, [
            'conversation_uuid' => $conversation->uuid,
            'handoff_uuid' => $handoff->uuid,
        ]);

        return $handoff;
    }
}
