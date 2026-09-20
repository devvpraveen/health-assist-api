<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\HealthGuide\SendGuestHealthGuideMessageAction;
use App\Actions\HealthGuide\StartGuestSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\HealthGuideConversationResource;
use App\Http\Resources\HealthGuideMessageResource;
use App\Http\Resources\RankedProviderResource;
use App\Models\GuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHealthGuideController extends Controller
{
    public function storeSession(Request $request, StartGuestSessionAction $action): JsonResponse
    {
        $data = $request->validate([
            'anonymous_id' => ['nullable', 'string', 'max:120'],
            'locale' => ['nullable', 'string', 'max:16'],
        ]);

        $result = $action->handle($data);

        return response()->json([
            'data' => [
                'guest_session' => [
                    'uuid' => $result['guest_session']->uuid,
                    'expires_at' => $result['guest_session']->expires_at,
                    'anonymous_id' => $result['guest_session']->anonymous_id,
                ],
                'conversation' => new HealthGuideConversationResource($result['conversation']),
                'opening_message' => new HealthGuideMessageResource($result['opening_message']),
                'user_turns' => $result['user_turns'],
                'max_user_turns' => $result['max_user_turns'],
                'auth_required' => $result['auth_required'],
                'continue' => $result['continue'],
            ],
            'disclaimer' => $result['disclaimer'],
        ], 201);
    }

    public function showSession(GuestSession $guest): JsonResponse
    {
        abort_unless($guest->tenant_id === \App\Support\TenantContext::id(), 404);

        $guest->load(['conversation.messages']);

        $maxTurns = (int) config('health_guide.guest.max_user_turns', 3);
        $userTurns = $guest->conversation
            ? $guest->conversation->messages->where('role', 'user')->count()
            : 0;

        $authRequired = ! $guest->isUsable()
            || $userTurns >= $maxTurns
            || $guest->conversation?->status === 'awaiting_auth';

        return response()->json([
            'data' => [
                'guest_session' => [
                    'uuid' => $guest->uuid,
                    'expires_at' => $guest->expires_at,
                    'claimed_at' => $guest->claimed_at,
                    'anonymous_id' => $guest->anonymous_id,
                ],
                'conversation' => $guest->conversation
                    ? new HealthGuideConversationResource($guest->conversation)
                    : null,
                'user_turns' => $userTurns,
                'max_user_turns' => $maxTurns,
                'auth_required' => $authRequired,
                'continue' => $authRequired ? [
                    'message' => config('health_guide.guest.continue_message'),
                    'options' => [
                        ['key' => 'mobile', 'label' => 'Continue with Mobile'],
                        ['key' => 'email', 'label' => 'Continue with Email'],
                        ['key' => 'google', 'label' => 'Continue with Google'],
                    ],
                ] : null,
            ],
            'disclaimer' => config('health_guide.disclaimer'),
        ]);
    }

    public function storeMessage(
        Request $request,
        GuestSession $guest,
        SendGuestHealthGuideMessageAction $action,
    ): JsonResponse {
        abort_unless($guest->tenant_id === \App\Support\TenantContext::id(), 404);

        $data = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        $result = $action->handle($guest, $data['content']);

        return response()->json([
            'message' => new HealthGuideMessageResource($result['message']),
            'conversation' => new HealthGuideConversationResource($result['conversation']),
            'guest_session' => [
                'uuid' => $result['guest_session']->uuid,
                'expires_at' => $result['guest_session']->expires_at,
            ],
            'structured_state' => $result['structured_state'],
            'safety' => $result['safety'],
            'recommendations' => RankedProviderResource::collection(collect($result['recommendations'])),
            'user_turns' => $result['user_turns'],
            'max_user_turns' => $result['max_user_turns'],
            'auth_required' => $result['auth_required'],
            'continue' => $result['continue'],
            'disclaimer' => $result['disclaimer'],
        ]);
    }
}
