<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\ProgressiveAuthAction;
use App\Actions\HealthGuide\ClaimGuestConversationAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\HealthGuideConversationResource;
use App\Http\Resources\UserResource;
use App\Models\GuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressiveAuthController extends Controller
{
    public function requestMobileOtp(Request $request, ProgressiveAuthAction $action): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:32'],
        ]);

        return response()->json([
            'data' => $action->requestMobileOtp($data['mobile']),
        ]);
    }

    public function verifyMobileOtp(Request $request, ProgressiveAuthAction $action): JsonResponse
    {
        $data = $request->validate([
            'challenge_uuid' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:16'],
            'guest_session_id' => ['nullable', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $result = $action->verifyMobileOtp(
            $data['challenge_uuid'],
            $data['code'],
            $data['guest_session_id'] ?? null,
            $data['device_name'] ?? 'api',
        );

        return $this->authResponse($result);
    }

    public function requestEmailOtp(Request $request, ProgressiveAuthAction $action): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        return response()->json([
            'data' => $action->requestEmailOtp($data['email']),
        ]);
    }

    public function verifyEmailOtp(Request $request, ProgressiveAuthAction $action): JsonResponse
    {
        $data = $request->validate([
            'challenge_uuid' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:16'],
            'guest_session_id' => ['nullable', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $result = $action->verifyEmailOtp(
            $data['challenge_uuid'],
            $data['code'],
            $data['guest_session_id'] ?? null,
            $data['device_name'] ?? 'api',
        );

        return $this->authResponse($result);
    }

    public function google(Request $request, ProgressiveAuthAction $action): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string', 'max:512'],
            'guest_session_id' => ['nullable', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $result = $action->authenticateGoogle(
            $data['id_token'],
            $data['guest_session_id'] ?? null,
            $data['device_name'] ?? 'api',
        );

        return $this->authResponse($result);
    }

    public function claimGuest(
        Request $request,
        ClaimGuestConversationAction $action,
    ): JsonResponse {
        $data = $request->validate([
            'guest_session_id' => ['required', 'uuid'],
        ]);

        $session = GuestSession::query()
            ->withoutGlobalScopes()
            ->where('uuid', $data['guest_session_id'])
            ->firstOrFail();

        $result = $action->handle($request->user(), $session);

        return response()->json([
            'data' => [
                'message' => $result['message'],
                'migrated' => $result['migrated'],
                'guest_session_uuid' => $result['guest_session']->uuid,
                'conversation' => new HealthGuideConversationResource($result['conversation']),
            ],
        ]);
    }

    /**
     * @param  array{token: string, user: \App\Models\User, migration: array<string, mixed>|null}  $result
     */
    private function authResponse(array $result): JsonResponse
    {
        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new UserResource($result['user']),
            'migration' => $result['migration'],
        ]);
    }
}
