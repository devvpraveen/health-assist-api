<?php

namespace App\Actions\HealthGuide;

use App\Models\GuestSession;
use App\Models\HealthGuideConversation;
use App\Models\HealthGuideMessage;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartGuestSessionAction
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array{anonymous_id?: string|null, locale?: string|null}  $data
     * @return array{
     *     guest_session: GuestSession,
     *     conversation: HealthGuideConversation,
     *     opening_message: HealthGuideMessage,
     *     user_turns: int,
     *     max_user_turns: int,
     *     auth_required: bool,
     *     continue: array<string, mixed>|null,
     *     disclaimer: string
     * }
     */
    public function handle(array $data = []): array
    {
        $tenantId = TenantContext::id();
        if ($tenantId === null) {
            throw ValidationException::withMessages([
                'tenant' => ['Tenant context is required.'],
            ]);
        }

        $maxTurns = (int) config('health_guide.guest.max_user_turns', 3);
        $ttlHours = (int) config('health_guide.guest.ttl_hours', 24);
        $opening = (string) config('health_guide.guest.opening_message');

        return DB::transaction(function () use ($data, $tenantId, $maxTurns, $ttlHours, $opening): array {
            $session = GuestSession::query()->create([
                'tenant_id' => $tenantId,
                'anonymous_id' => $data['anonymous_id'] ?? null,
                'expires_at' => now()->addHours($ttlHours),
                'meta' => [
                    'locale' => $data['locale'] ?? null,
                    'started_at' => now()->toIso8601String(),
                ],
            ]);

            $conversation = HealthGuideConversation::query()->create([
                'tenant_id' => $tenantId,
                'patient_id' => null,
                'user_id' => null,
                'guest_session_id' => $session->id,
                'status' => HealthGuideConversation::STATUS_ACTIVE,
                'locale' => $data['locale'] ?? null,
                'structured_state' => [
                    'intent' => null,
                    'complaint' => null,
                    'duration' => null,
                    'severity' => null,
                    'care_category' => null,
                    'urgency' => null,
                    'missing_fields' => ['complaint', 'duration', 'severity'],
                    'guest' => true,
                ],
                'last_message_at' => now(),
            ]);

            $session->conversation_id = $conversation->id;
            $session->save();

            $openingMessage = HealthGuideMessage::query()->create([
                'conversation_id' => $conversation->id,
                'tenant_id' => $tenantId,
                'role' => HealthGuideMessage::ROLE_ASSISTANT,
                'content' => $opening,
                'meta' => ['type' => 'guest_opening'],
            ]);

            $this->auditLogger->log('health_guide.guest.started', $conversation, [
                'guest_session_uuid' => $session->uuid,
                'conversation_uuid' => $conversation->uuid,
            ]);

            $conversation = $conversation->fresh(['messages']);

            return [
                'guest_session' => $session->fresh(),
                'conversation' => $conversation,
                'opening_message' => $openingMessage,
                'user_turns' => 0,
                'max_user_turns' => $maxTurns,
                'auth_required' => false,
                'continue' => null,
                'disclaimer' => (string) config('health_guide.disclaimer'),
            ];
        });
    }
}
