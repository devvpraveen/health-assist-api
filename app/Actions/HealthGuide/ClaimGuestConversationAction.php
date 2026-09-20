<?php

namespace App\Actions\HealthGuide;

use App\Models\GuestSession;
use App\Models\HealthGuideConversation;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimGuestConversationAction
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    /**
     * @return array{
     *     guest_session: GuestSession,
     *     conversation: HealthGuideConversation,
     *     migrated: bool,
     *     message: string
     * }
     */
    public function handle(User $user, GuestSession $session): array
    {
        if ($session->isExpired() && ! $session->isClaimed()) {
            throw ValidationException::withMessages([
                'guest_session_id' => ['This guest session has expired.'],
            ]);
        }

        // Idempotent: already claimed by this user.
        if ($session->isClaimed() && (int) $session->claimed_by_user_id === (int) $user->id) {
            $conversation = $session->conversation?->load(['messages', 'patient']);
            if ($conversation === null) {
                throw ValidationException::withMessages([
                    'guest_session_id' => ['Guest conversation is missing.'],
                ]);
            }

            return [
                'guest_session' => $session,
                'conversation' => $conversation,
                'migrated' => false,
                'message' => 'My conversation has been saved.',
            ];
        }

        if ($session->isClaimed()) {
            throw ValidationException::withMessages([
                'guest_session_id' => ['This guest session was claimed by another account.'],
            ]);
        }

        return DB::transaction(function () use ($user, $session): array {
            TenantContext::set($session->tenant_id);

            if ($user->tenant_id === null) {
                $user->forceFill(['tenant_id' => $session->tenant_id])->save();
            } elseif ((int) $user->tenant_id !== (int) $session->tenant_id) {
                throw ValidationException::withMessages([
                    'guest_session_id' => ['Guest session belongs to a different tenant.'],
                ]);
            }

            $conversation = $session->conversation;
            if ($conversation === null) {
                throw ValidationException::withMessages([
                    'guest_session_id' => ['Guest conversation is missing.'],
                ]);
            }

            $patient = Patient::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $session->tenant_id)
                ->where('user_id', $user->id)
                ->first();

            if ($patient === null) {
                $patient = Patient::query()->create([
                    'tenant_id' => $session->tenant_id,
                    'user_id' => $user->id,
                    'first_name' => $this->firstNameFromUser($user),
                    'last_name' => 'Patient',
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => 'active',
                    'consent_status' => 'pending',
                ]);
            }

            $conversation->user_id = $user->id;
            $conversation->patient_id = $patient->id;
            $conversation->guest_session_id = $session->id;
            if ($conversation->status === HealthGuideConversation::STATUS_AWAITING_AUTH) {
                $conversation->status = HealthGuideConversation::STATUS_ACTIVE;
            }
            $state = $conversation->structured_state ?? [];
            $state['guest'] = false;
            $conversation->structured_state = $state;
            $conversation->save();

            $session->claimed_at = now();
            $session->claimed_by_user_id = $user->id;
            $session->expires_at = now();
            $session->save();

            $this->auditLogger->log('health_guide.guest.claimed', $conversation, [
                'guest_session_uuid' => $session->uuid,
                'conversation_uuid' => $conversation->uuid,
                'user_id' => $user->id,
            ], $user);

            return [
                'guest_session' => $session->fresh(),
                'conversation' => $conversation->fresh(['messages', 'patient']),
                'migrated' => true,
                'message' => 'My conversation has been saved.',
            ];
        });
    }

    private function firstNameFromUser(User $user): string
    {
        $name = trim((string) $user->name);
        if ($name !== '' && ! str_contains($name, '@')) {
            return explode(' ', $name)[0] ?: 'Patient';
        }

        if ($user->email) {
            return explode('@', $user->email)[0] ?: 'Patient';
        }

        return 'Patient';
    }
}
