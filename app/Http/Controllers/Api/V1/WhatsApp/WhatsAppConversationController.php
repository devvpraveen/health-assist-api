<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Actions\WhatsApp\AcceptWhatsAppHandoffAction;
use App\Actions\WhatsApp\CreateWhatsAppHandoffAction;
use App\Actions\WhatsApp\EnableWhatsAppAiAction;
use App\Actions\WhatsApp\ResolveWhatsAppHandoffAction;
use App\Actions\WhatsApp\SendStaffWhatsAppMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WhatsApp\SendWhatsAppStaffMessageRequest;
use App\Http\Requests\Api\V1\WhatsApp\StoreWhatsAppHandoffRequest;
use App\Http\Resources\WhatsApp\WhatsAppConversationResource;
use App\Http\Resources\WhatsApp\WhatsAppHandoffResource;
use App\Http\Resources\WhatsApp\WhatsAppMessageResource;
use App\Models\WhatsAppConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WhatsAppConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WhatsAppConversation::class);

        $conversations = WhatsAppConversation::query()
            ->with(['patient', 'account'])
            ->when($request->filled('state'), fn ($q) => $q->where('state', $request->string('state')->toString()))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate();

        return WhatsAppConversationResource::collection($conversations);
    }

    public function show(WhatsAppConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['patient', 'account', 'messages' => fn ($q) => $q->orderBy('id'), 'handoffs']);

        return response()->json([
            'data' => new WhatsAppConversationResource($conversation),
        ]);
    }

    public function storeMessage(
        SendWhatsAppStaffMessageRequest $request,
        WhatsAppConversation $conversation,
        SendStaffWhatsAppMessageAction $action,
    ): JsonResponse {
        $this->authorize('manage', $conversation);

        $message = $action->handle($conversation, $request->validated('body'));

        return response()->json([
            'data' => new WhatsAppMessageResource($message),
            'conversation' => new WhatsAppConversationResource($conversation->fresh(['patient', 'account'])),
        ], 201);
    }

    public function storeHandoff(
        StoreWhatsAppHandoffRequest $request,
        WhatsAppConversation $conversation,
        CreateWhatsAppHandoffAction $action,
    ): JsonResponse {
        $this->authorize('manage', $conversation);

        $handoff = $action->handle($conversation, $request->validated());

        return response()->json([
            'data' => new WhatsAppHandoffResource($handoff),
        ], 201);
    }

    public function acceptHandoff(
        WhatsAppConversation $conversation,
        AcceptWhatsAppHandoffAction $action,
    ): JsonResponse {
        $this->authorize('manage', $conversation);

        return response()->json([
            'data' => new WhatsAppHandoffResource($action->handle($conversation)),
            'conversation' => new WhatsAppConversationResource($conversation->fresh()),
        ]);
    }

    public function resolveHandoff(
        WhatsAppConversation $conversation,
        ResolveWhatsAppHandoffAction $action,
    ): JsonResponse {
        $this->authorize('manage', $conversation);

        return response()->json([
            'data' => new WhatsAppHandoffResource($action->handle($conversation)),
        ]);
    }

    public function enableAi(
        WhatsAppConversation $conversation,
        EnableWhatsAppAiAction $action,
    ): JsonResponse {
        $this->authorize('manage', $conversation);

        return response()->json([
            'data' => new WhatsAppConversationResource($action->handle($conversation)),
        ]);
    }
}
