<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Marketing\CreateMarketingLeadAction;
use App\Actions\Marketing\UpdateMarketingLeadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketing\StoreMarketingLeadRequest;
use App\Http\Requests\Api\V1\Marketing\StorePublicMarketingLeadRequest;
use App\Http\Requests\Api\V1\Marketing\UpdateMarketingLeadRequest;
use App\Http\Resources\MarketingLeadResource;
use App\Models\MarketingLead;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MarketingLeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MarketingLead::class);

        $query = MarketingLead::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return MarketingLeadResource::collection($query->paginate());
    }

    public function store(
        StoreMarketingLeadRequest $request,
        CreateMarketingLeadAction $action,
    ): JsonResponse {
        $lead = $action->handle($request->validated());

        return (new MarketingLeadResource($lead))
            ->response()
            ->setStatusCode(201);
    }

    public function storePublic(
        StorePublicMarketingLeadRequest $request,
        CreateMarketingLeadAction $action,
    ): JsonResponse {
        $data = $request->validated();

        if (! empty($data['website'] ?? null)) {
            return response()->json(['data' => ['accepted' => true]], 201);
        }

        unset($data['website']);
        $data['status'] = MarketingLead::STATUS_NEW;

        $lead = $action->handle($data);

        return (new MarketingLeadResource($lead))
            ->response()
            ->setStatusCode(201);
    }

    public function show(MarketingLead $lead): MarketingLeadResource
    {
        $this->authorize('view', $lead);

        return new MarketingLeadResource($lead);
    }

    public function update(
        UpdateMarketingLeadRequest $request,
        MarketingLead $lead,
        UpdateMarketingLeadAction $action,
    ): MarketingLeadResource {
        return new MarketingLeadResource($action->handle($lead, $request->validated()));
    }

    public function destroy(MarketingLead $lead, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $lead);

        $auditLogger->log('marketing.lead.deleted', $lead, [
            'lead_uuid' => $lead->uuid,
        ]);

        $lead->delete();

        return response()->noContent();
    }
}
