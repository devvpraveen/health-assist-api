<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\HealthGuide\AssistBookingAction;
use App\Actions\HealthGuide\RecomputeRecommendationsAction;
use App\Actions\HealthGuide\SendHealthGuideMessageAction;
use App\Actions\HealthGuide\StartConversationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HealthGuide\AssessSafetyRequest;
use App\Http\Requests\Api\V1\HealthGuide\AssistHealthGuideBookingRequest;
use App\Http\Requests\Api\V1\HealthGuide\SendHealthGuideMessageRequest;
use App\Http\Requests\Api\V1\HealthGuide\StartHealthGuideConversationRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\HealthGuideConversationResource;
use App\Http\Resources\HealthGuideMessageResource;
use App\Http\Resources\RankedProviderResource;
use App\Http\Resources\SafetyRuleResource;
use App\Models\HealthGuideConversation;
use App\Models\SafetyRule;
use App\Services\Safety\SafetyEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HealthGuideController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', HealthGuideConversation::class);

        $conversations = HealthGuideConversation::query()
            ->with(['patient'])
            ->when(
                ! $request->user()?->isSuperAdmin()
                    && ! $request->user()?->hasPermission('health_guide.view'),
                fn ($q) => $q->where('user_id', $request->user()?->id),
            )
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate();

        return HealthGuideConversationResource::collection($conversations);
    }

    public function store(
        StartHealthGuideConversationRequest $request,
        StartConversationAction $action,
    ): JsonResponse {
        $this->authorize('create', HealthGuideConversation::class);

        $conversation = $action->handle($request->validated());

        return response()->json([
            'data' => new HealthGuideConversationResource($conversation),
            'disclaimer' => config('health_guide.disclaimer'),
        ], 201);
    }

    public function show(HealthGuideConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['messages', 'patient']);

        return response()->json([
            'data' => new HealthGuideConversationResource($conversation),
            'disclaimer' => config('health_guide.disclaimer'),
        ]);
    }

    public function storeMessage(
        SendHealthGuideMessageRequest $request,
        HealthGuideConversation $conversation,
        SendHealthGuideMessageAction $action,
    ): JsonResponse {
        $this->authorize('run', $conversation);

        $result = $action->handle($conversation, $request->validated('content'));

        return response()->json([
            'message' => new HealthGuideMessageResource($result['message']),
            'conversation' => new HealthGuideConversationResource($result['conversation']),
            'structured_state' => $result['structured_state'],
            'safety' => $result['safety'],
            'recommendations' => RankedProviderResource::collection(collect($result['recommendations'])),
            'disclaimer' => $result['disclaimer'],
        ]);
    }

    public function recommendations(
        HealthGuideConversation $conversation,
        RecomputeRecommendationsAction $action,
    ): JsonResponse {
        $this->authorize('run', $conversation);

        $result = $action->handle($conversation);

        return response()->json([
            'conversation' => new HealthGuideConversationResource($result['conversation']),
            'structured_state' => $result['structured_state'],
            'safety' => $result['safety'],
            'recommendations' => RankedProviderResource::collection(collect($result['recommendations'])),
            'disclaimer' => $result['disclaimer'],
        ]);
    }

    public function book(
        AssistHealthGuideBookingRequest $request,
        HealthGuideConversation $conversation,
        AssistBookingAction $action,
    ): JsonResponse {
        $this->authorize('run', $conversation);

        $result = $action->handle($conversation, $request->validated());

        return response()->json([
            'data' => [
                'appointment' => new AppointmentResource($result['appointment']),
                'conversation' => new HealthGuideConversationResource($result['conversation']),
            ],
            'disclaimer' => $result['disclaimer'],
        ], 201);
    }

    public function safetyRules(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        abort_unless(
            $user && (
                $user->isSuperAdmin()
                || $user->hasPermission('ai.manage')
                || $user->hasPermission('health_guide.view')
            ),
            403
        );

        $rules = SafetyRule::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate();

        return SafetyRuleResource::collection($rules);
    }

    public function assessSafety(
        AssessSafetyRequest $request,
        SafetyEngine $safetyEngine,
    ): JsonResponse {
        $this->authorize('create', HealthGuideConversation::class);

        $result = $safetyEngine->assess($request->validated('text'), null, [
            'persist' => false,
        ]);

        return response()->json([
            'data' => $result->toPayload(),
            'disclaimer' => config('health_guide.disclaimer'),
        ]);
    }
}
