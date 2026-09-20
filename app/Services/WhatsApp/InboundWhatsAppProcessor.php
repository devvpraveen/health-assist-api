<?php

namespace App\Services\WhatsApp;

use App\Actions\HealthGuide\AssistBookingAction;
use App\Actions\HealthGuide\SendHealthGuideMessageAction;
use App\Actions\HealthGuide\StartConversationAction;
use App\Contracts\WhatsApp\WhatsAppGatewayInterface;
use App\Models\HealthGuideConversation;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppHandoff;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppWebhookEvent;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Services\Safety\SafetyEngine;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class InboundWhatsAppProcessor
{
    public function __construct(
        private EvolutionWebhookParser $parser,
        private PatientPhoneMatcher $patientMatcher,
        private WhatsAppGatewayInterface $gateway,
        private SafetyEngine $safetyEngine,
        private StartConversationAction $startConversationAction,
        private SendHealthGuideMessageAction $sendHealthGuideMessageAction,
        private AssistBookingAction $assistBookingAction,
        private AIOrchestrator $orchestrator,
    ) {}

    public function processWebhookEvent(WhatsAppWebhookEvent $event): void
    {
        $account = $event->account_id
            ? WhatsAppAccount::query()->withoutGlobalScopes()->find($event->account_id)
            : null;

        if ($account === null || ! $account->is_active) {
            $event->update([
                'processed_at' => now(),
                'error' => 'Account missing or inactive',
            ]);

            return;
        }

        TenantContext::set($account->tenant_id);

        try {
            $messages = $this->parser->parseMessages($event->payload ?? []);

            foreach ($messages as $parsed) {
                $this->handleParsedMessage($account, $parsed);
            }

            $event->update([
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $event->update([
                'processed_at' => now(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            TenantContext::clear();
        }
    }

    /**
     * @param  array{
     *     event: string,
     *     instance: string|null,
     *     remote_jid: string|null,
     *     remote_phone: string,
     *     from_me: bool,
     *     message_id: string|null,
     *     type: string,
     *     text: string|null,
     *     media_stub: array<string, mixed>|null,
     *     raw: array<string, mixed>
     * }  $parsed
     */
    public function handleParsedMessage(WhatsAppAccount $account, array $parsed): void
    {
        $conversation = $this->findOrCreateConversation($account, $parsed);

        if ($parsed['from_me']) {
            $this->storeOutboundEcho($account, $conversation, $parsed);

            return;
        }

        $inbound = WhatsAppMessage::query()->create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $account->tenant_id,
            'account_id' => $account->id,
            'direction' => WhatsAppMessage::DIRECTION_INBOUND,
            'type' => $parsed['type'],
            'body' => $parsed['text'],
            'evolution_message_id' => $parsed['message_id'],
            'payload' => array_filter([
                'media' => $parsed['media_stub'],
                'raw_keys' => array_keys($parsed['raw']['message'] ?? []),
            ]),
            'status' => WhatsAppMessage::STATUS_RECEIVED,
        ]);

        $conversation->last_message_at = now();
        $conversation->save();

        $text = trim((string) ($parsed['text'] ?? ''));

        if ($conversation->isHumanManaged()) {
            Log::info('WhatsApp inbound while human-managed; skipping AI', [
                'conversation_uuid' => $conversation->uuid,
                'state' => $conversation->state,
                'message_uuid' => $inbound->uuid,
            ]);

            return;
        }

        if ($text === '' && $parsed['type'] !== 'text') {
            $this->reply(
                $account,
                $conversation,
                'Thanks — we received your media. A team member can review it. For help in text, describe your question, or say "talk to a person".',
            );

            return;
        }

        if ($text === '') {
            return;
        }

        if ($this->isHandoffRequest($text)) {
            $this->handleHandoff($account, $conversation, $text);

            return;
        }

        if ($this->isClinicFacingIntent($text)) {
            $this->handleClinicIntent($account, $conversation, $text);

            return;
        }

        if ($this->tryBookingCommand($account, $conversation, $text)) {
            return;
        }

        $this->handleHealthGuide($account, $conversation, $text);
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function findOrCreateConversation(WhatsAppAccount $account, array $parsed): WhatsAppConversation
    {
        $phone = $parsed['remote_phone'];

        $conversation = WhatsAppConversation::query()
            ->withoutGlobalScopes()
            ->where('account_id', $account->id)
            ->where('remote_phone', $phone)
            ->first();

        $patient = $this->patientMatcher->findForTenant($account->tenant_id, $phone);

        if ($conversation === null) {
            $conversation = WhatsAppConversation::query()->create([
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'patient_id' => $patient?->id,
                'remote_phone' => $phone,
                'remote_jid' => $parsed['remote_jid'],
                'state' => WhatsAppConversation::STATE_AI,
            ]);
        } elseif ($conversation->patient_id === null && $patient !== null) {
            $conversation->patient_id = $patient->id;
            $conversation->save();
        }

        if ($parsed['remote_jid'] && $conversation->remote_jid !== $parsed['remote_jid']) {
            $conversation->remote_jid = $parsed['remote_jid'];
            $conversation->save();
        }

        return $conversation;
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    private function storeOutboundEcho(WhatsAppAccount $account, WhatsAppConversation $conversation, array $parsed): void
    {
        $exists = $parsed['message_id']
            ? WhatsAppMessage::query()
                ->withoutGlobalScopes()
                ->where('account_id', $account->id)
                ->where('evolution_message_id', $parsed['message_id'])
                ->exists()
            : false;

        if ($exists) {
            return;
        }

        WhatsAppMessage::query()->create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $account->tenant_id,
            'account_id' => $account->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'type' => $parsed['type'],
            'body' => $parsed['text'],
            'evolution_message_id' => $parsed['message_id'],
            'payload' => ['echo' => true],
            'status' => WhatsAppMessage::STATUS_SENT,
        ]);

        $conversation->last_message_at = now();
        $conversation->save();
    }

    private function isHandoffRequest(string $text): bool
    {
        $normalized = mb_strtolower(trim($text));
        $phrases = (array) config('whatsapp.handoff_phrases', []);

        foreach ($phrases as $phrase) {
            $phrase = mb_strtolower((string) $phrase);
            if ($phrase !== '' && (str_contains($normalized, $phrase) || $normalized === $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('/\b(talk to (a )?(person|human|agent)|human help)\b/i', $normalized);
    }

    private function handleHandoff(WhatsAppAccount $account, WhatsAppConversation $conversation, string $text): void
    {
        $summary = $this->buildHandoffSummary($account, $conversation, $text);

        $conversation->state = WhatsAppConversation::STATE_WAITING_HUMAN;
        $conversation->handoff_summary = $summary;
        $conversation->save();

        WhatsAppHandoff::query()->create([
            'tenant_id' => $account->tenant_id,
            'conversation_id' => $conversation->id,
            'requested_by' => WhatsAppHandoff::REQUESTED_BY_PATIENT,
            'status' => WhatsAppHandoff::STATUS_OPEN,
            'summary' => $summary,
            'urgency' => 'normal',
        ]);

        $this->reply(
            $account,
            $conversation,
            (string) config('whatsapp.handoff_acknowledgement'),
        );
    }

    private function buildHandoffSummary(WhatsAppAccount $account, WhatsAppConversation $conversation, string $text): string
    {
        try {
            $result = $this->orchestrator->run('receptionist', new AgentContext(
                tenantId: (int) $account->tenant_id,
                userId: null,
                feature: 'whatsapp.handoff_summary',
                input: 'Summarize this WhatsApp handoff without PHI identifiers: '.$text,
                metadata: [
                    'conversation_uuid' => $conversation->uuid,
                    'redacted' => true,
                ],
            ));

            return $result->content !== ''
                ? $result->content
                : 'Patient requested human assistance via WhatsApp.';
        } catch (Throwable) {
            return 'Patient requested human assistance via WhatsApp.';
        }
    }

    private function isClinicFacingIntent(string $text): bool
    {
        $normalized = mb_strtolower($text);

        return (bool) preg_match(
            '/\b(hours|open|opening|location|address|parking|clinic (info|details)|reception|timing|timings)\b/i',
            $normalized,
        );
    }

    private function handleClinicIntent(WhatsAppAccount $account, WhatsAppConversation $conversation, string $text): void
    {
        try {
            $result = $this->orchestrator->run('receptionist', new AgentContext(
                tenantId: (int) $account->tenant_id,
                userId: null,
                feature: 'whatsapp.clinic_receptionist',
                input: $text,
                metadata: [
                    'conversation_uuid' => $conversation->uuid,
                    'channel' => 'whatsapp',
                ],
            ));
            $reply = $result->content !== ''
                ? $result->content
                : 'For clinic hours and location, please contact the front desk, or say "talk to a person".';
        } catch (Throwable) {
            $reply = 'For clinic hours and location, please contact the front desk, or say "talk to a person".';
        }

        $this->reply($account, $conversation, $reply);
    }

    private function tryBookingCommand(WhatsAppAccount $account, WhatsAppConversation $conversation, string $text): bool
    {
        if (! preg_match('/^BOOK\s+(\d+)\s+(\S+)(?:\s+(\d+))?$/i', trim($text), $m)) {
            return false;
        }

        $providerId = (int) $m[1];
        $startsAt = $m[2];
        $duration = isset($m[3]) ? (int) $m[3] : 30;

        $guide = $this->ensureHealthGuideConversation($conversation);

        $safety = $this->safetyEngine->assess($text, $guide->structured_state, [
            'persist' => true,
            'conversation_id' => $guide->id,
            'patient_id' => $conversation->patient_id,
            'tenant_id' => $account->tenant_id,
        ]);

        if ($safety->isEscalation() || ! $safety->allowsBooking()) {
            $this->reply(
                $account,
                $conversation,
                $safety->message !== ''
                    ? $safety->message
                    : 'Booking is not available due to a safety check. Please seek urgent care if needed, or talk to a person.',
            );

            return true;
        }

        if ($conversation->patient_id === null) {
            $this->reply(
                $account,
                $conversation,
                'We could not match your WhatsApp number to a patient profile. Please contact the clinic or talk to a person to book.',
            );

            return true;
        }

        try {
            $result = $this->assistBookingAction->handle($guide, [
                'confirm' => true,
                'patient_id' => $conversation->patient_id,
                'provider_id' => $providerId,
                'starts_at' => $startsAt,
                'duration_minutes' => $duration,
                'meta' => ['source' => 'whatsapp'],
            ]);

            $appointment = $result['appointment'];
            $this->reply(
                $account,
                $conversation,
                'Booked: appointment '.$appointment->uuid.' on '.
                ($appointment->starts_at?->toDayDateTimeString() ?? $startsAt).
                '. Reply BOOK <providerId> <iso8601> to book another slot, or talk to a person for help.',
            );
        } catch (ValidationException $e) {
            $this->reply(
                $account,
                $conversation,
                'Booking failed: '.collect($e->errors())->flatten()->implode(' '),
            );
        } catch (Throwable $e) {
            $this->reply($account, $conversation, 'Booking failed. Please try again or talk to a person.');
            Log::warning('WhatsApp BOOK failed', ['error' => $e->getMessage()]);
        }

        return true;
    }

    private function handleHealthGuide(WhatsAppAccount $account, WhatsAppConversation $conversation, string $text): void
    {
        $guide = $this->ensureHealthGuideConversation($conversation);

        $result = $this->sendHealthGuideMessageAction->handle($guide, $text);
        $reply = (string) ($result['message']->content ?? '');

        $recommendations = $result['recommendations'] ?? [];
        if ($recommendations !== []) {
            $top = $recommendations[0];
            $slots = $top['next_slots'] ?? [];
            $slotHint = '';
            if ($slots !== [] && isset($slots[0]['starts_at'])) {
                $slotHint = ' Next slot: '.$slots[0]['starts_at'].'. To book reply: BOOK '.$top['id'].' '.$slots[0]['starts_at'];
            } elseif (isset($top['id'])) {
                $slotHint = ' Top match: '.$top['display_name'].' (id '.$top['id'].'). To book: BOOK '.$top['id'].' <iso8601>';
            }
            $reply = trim($reply.$slotHint);
        }

        $disclaimer = (string) ($result['disclaimer'] ?? config('health_guide.disclaimer', ''));
        if ($disclaimer !== '' && ! str_contains($reply, 'not a diagnosis')) {
            $reply = trim($reply."\n\n".$disclaimer);
        }

        $this->reply($account, $conversation, $reply);
    }

    private function ensureHealthGuideConversation(WhatsAppConversation $conversation): HealthGuideConversation
    {
        if ($conversation->health_guide_conversation_id) {
            $existing = HealthGuideConversation::query()
                ->withoutGlobalScopes()
                ->find($conversation->health_guide_conversation_id);

            if ($existing !== null) {
                return $existing;
            }
        }

        TenantContext::set($conversation->tenant_id);

        $guide = $this->startConversationAction->handle([
            'patient_id' => $conversation->patient_id,
            'locale' => null,
        ]);

        $conversation->health_guide_conversation_id = $guide->id;
        $conversation->save();

        return $guide;
    }

    private function reply(WhatsAppAccount $account, WhatsAppConversation $conversation, string $text): void
    {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $result = $this->gateway->sendText(
            $account->instance_name,
            $conversation->remote_phone,
            $text,
        );

        WhatsAppMessage::query()->create([
            'conversation_id' => $conversation->id,
            'tenant_id' => $account->tenant_id,
            'account_id' => $account->id,
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'type' => WhatsAppMessage::TYPE_TEXT,
            'body' => $text,
            'evolution_message_id' => $result->messageId,
            'payload' => ['gateway' => $result->raw],
            'status' => $result->success ? WhatsAppMessage::STATUS_SENT : WhatsAppMessage::STATUS_FAILED,
        ]);

        $conversation->last_message_at = now();
        $conversation->save();
    }
}
