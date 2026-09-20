<?php

namespace App\Actions\HealthGuide;

use App\Models\GuestSession;
use App\Models\HealthGuideConversation;
use App\Models\HealthGuideMessage;
use App\Support\TenantContext;
use Illuminate\Validation\ValidationException;

class SendGuestHealthGuideMessageAction
{
    public function __construct(
        private SendHealthGuideMessageAction $sendMessageAction,
    ) {}

    /**
     * @return array{
     *     guest_session: GuestSession,
     *     conversation: HealthGuideConversation,
     *     message: HealthGuideMessage,
     *     structured_state: array<string, mixed>,
     *     safety: array<string, mixed>,
     *     recommendations: list<array<string, mixed>>,
     *     user_turns: int,
     *     max_user_turns: int,
     *     auth_required: bool,
     *     continue: array<string, mixed>|null,
     *     disclaimer: string
     * }
     */
    public function handle(GuestSession $session, string $content): array
    {
        if (! $session->isUsable()) {
            throw ValidationException::withMessages([
                'guest_session' => [$session->isClaimed()
                    ? 'This guest session has already been claimed.'
                    : 'This guest session has expired.'],
            ]);
        }

        TenantContext::set($session->tenant_id);

        $conversation = $session->conversation;
        if ($conversation === null) {
            throw ValidationException::withMessages([
                'guest_session' => ['Guest conversation is missing.'],
            ]);
        }

        $maxTurns = (int) config('health_guide.guest.max_user_turns', 3);
        $userTurns = HealthGuideMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', HealthGuideMessage::ROLE_USER)
            ->count();

        if ($userTurns >= $maxTurns || $conversation->status === HealthGuideConversation::STATUS_AWAITING_AUTH) {
            abort(response()->json([
                'message' => (string) config('health_guide.guest.auth_required_message'),
                'auth_required' => true,
                'continue' => [
                    'message' => config('health_guide.guest.continue_message'),
                    'options' => [
                        ['key' => 'mobile', 'label' => 'Continue with Mobile'],
                        ['key' => 'email', 'label' => 'Continue with Email'],
                        ['key' => 'google', 'label' => 'Continue with Google'],
                    ],
                ],
            ], 403));
        }

        $result = $this->sendMessageAction->handle($conversation, $content);
        $conversation = $result['conversation'];

        $userTurnsAfter = HealthGuideMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('role', HealthGuideMessage::ROLE_USER)
            ->count();

        $authRequired = $userTurnsAfter >= $maxTurns
            || $conversation->status === HealthGuideConversation::STATUS_ESCALATED;

        $continue = null;
        if ($authRequired) {
            if ($conversation->status !== HealthGuideConversation::STATUS_ESCALATED) {
                $conversation->status = HealthGuideConversation::STATUS_AWAITING_AUTH;
                $conversation->save();
            }

            $continueMessage = (string) config('health_guide.guest.continue_message');
            HealthGuideMessage::query()->create([
                'conversation_id' => $conversation->id,
                'tenant_id' => $session->tenant_id,
                'role' => HealthGuideMessage::ROLE_ASSISTANT,
                'content' => $continueMessage,
                'meta' => ['type' => 'guest_continue_prompt'],
            ]);

            $continue = [
                'message' => $continueMessage,
                'options' => [
                    ['key' => 'mobile', 'label' => 'Continue with Mobile'],
                    ['key' => 'email', 'label' => 'Continue with Email'],
                    ['key' => 'google', 'label' => 'Continue with Google'],
                ],
            ];

            $conversation = $conversation->fresh(['messages', 'patient']);
        }

        return [
            'guest_session' => $session->fresh(),
            'conversation' => $conversation,
            'message' => $result['message'],
            'structured_state' => $result['structured_state'],
            'safety' => $result['safety'],
            'recommendations' => $result['recommendations'],
            'user_turns' => $userTurnsAfter,
            'max_user_turns' => $maxTurns,
            'auth_required' => $authRequired,
            'continue' => $continue,
            'disclaimer' => $result['disclaimer'],
        ];
    }
}
