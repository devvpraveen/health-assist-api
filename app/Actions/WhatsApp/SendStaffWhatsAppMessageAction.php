<?php

namespace App\Actions\WhatsApp;

use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppHandoff;
use App\Models\WhatsAppMessage;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SendStaffWhatsAppMessageAction
{
    public function __construct(
        private WhatsAppGatewayInterface $gateway,
        private AuditLogger $auditLogger,
    ) {}

    public function handle(WhatsAppConversation $conversation, string $body): WhatsAppMessage
    {
        $account = $conversation->account;
        if ($account === null || ! $account->is_active) {
            throw ValidationException::withMessages([
                'account' => ['WhatsApp account is missing or inactive.'],
            ]);
        }

        $result = $this->gateway->sendText(
            $account->instance_name,
            $conversation->remote_phone,
            $body,
        );

        $message = WhatsAppMessage::query()->create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $conversation->tenant_id,
            'account_id' => $account->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'type' => WhatsAppMessage::TYPE_TEXT,
            'body' => $body,
            'evolution_message_id' => $result->messageId,
            'payload' => ['staff_user_id' => Auth::id(), 'gateway' => $result->raw],
            'status' => $result->success ? WhatsAppMessage::STATUS_SENT : WhatsAppMessage::STATUS_FAILED,
        ]);

        $conversation->state = WhatsAppConversation::STATE_HUMAN;
        $conversation->last_message_at = now();
        $conversation->save();

        $this->auditLogger->log('whatsapp.message.staff_sent', $conversation, [
            'conversation_uuid' => $conversation->uuid,
            'message_uuid' => $message->uuid,
        ]);

        return $message;
    }
}
