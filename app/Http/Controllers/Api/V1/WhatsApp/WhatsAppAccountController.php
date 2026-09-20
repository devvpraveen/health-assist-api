<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Actions\WhatsApp\ConfigureWhatsAppWebhookAction;
use App\Actions\WhatsApp\CreateWhatsAppAccountAction;
use App\Actions\WhatsApp\UpdateWhatsAppAccountAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\WhatsApp\StoreWhatsAppAccountRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateWhatsAppAccountRequest;
use App\Http\Resources\WhatsApp\WhatsAppAccountResource;
use App\Models\WhatsAppAccount;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WhatsAppAccountController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WhatsAppAccount::class);

        $accounts = WhatsAppAccount::query()
            ->orderBy('name')
            ->paginate();

        return WhatsAppAccountResource::collection($accounts);
    }

    public function store(
        StoreWhatsAppAccountRequest $request,
        CreateWhatsAppAccountAction $action,
    ): JsonResponse {
        $this->authorize('create', WhatsAppAccount::class);

        $account = $action->handle($request->validated());

        return (new WhatsAppAccountResource($account))
            ->response()
            ->setStatusCode(201);
    }

    public function show(WhatsAppAccount $account): WhatsAppAccountResource
    {
        $this->authorize('view', $account);

        return new WhatsAppAccountResource($account);
    }

    public function update(
        UpdateWhatsAppAccountRequest $request,
        WhatsAppAccount $account,
        UpdateWhatsAppAccountAction $action,
    ): WhatsAppAccountResource {
        $this->authorize('update', $account);

        return new WhatsAppAccountResource($action->handle($account, $request->validated()));
    }

    public function destroy(WhatsAppAccount $account, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $account);

        $auditLogger->log('whatsapp.account.deleted', $account, [
            'account_uuid' => $account->uuid,
        ]);

        $account->delete();

        return response()->noContent();
    }

    public function configureWebhook(
        WhatsAppAccount $account,
        ConfigureWhatsAppWebhookAction $action,
    ): JsonResponse {
        $this->authorize('manage', $account);

        $url = $action->handle($account);

        return response()->json([
            'data' => new WhatsAppAccountResource($account->fresh()),
            'webhook_url' => $url,
        ]);
    }
}
