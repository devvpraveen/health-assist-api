<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Marketing\CreateAudienceSegmentAction;
use App\Actions\Marketing\UpdateAudienceSegmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketing\StoreAudienceSegmentRequest;
use App\Http\Requests\Api\V1\Marketing\UpdateAudienceSegmentRequest;
use App\Http\Resources\AudienceSegmentResource;
use App\Models\AudienceSegment;
use App\Services\AuditLogger;
use App\Services\Marketing\SegmentEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AudienceSegmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AudienceSegment::class);

        return AudienceSegmentResource::collection(
            AudienceSegment::query()->latest()->paginate()
        );
    }

    public function store(
        StoreAudienceSegmentRequest $request,
        CreateAudienceSegmentAction $action,
    ): JsonResponse {
        $segment = $action->handle($request->validated());

        return (new AudienceSegmentResource($segment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AudienceSegment $segment): AudienceSegmentResource
    {
        $this->authorize('view', $segment);

        return new AudienceSegmentResource($segment);
    }

    public function update(
        UpdateAudienceSegmentRequest $request,
        AudienceSegment $segment,
        UpdateAudienceSegmentAction $action,
    ): AudienceSegmentResource {
        return new AudienceSegmentResource($action->handle($segment, $request->validated()));
    }

    public function destroy(AudienceSegment $segment, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $segment);

        $auditLogger->log('marketing.segment.deleted', $segment, [
            'key' => $segment->key,
        ]);

        $segment->delete();

        return response()->noContent();
    }

    public function members(string $key, SegmentEvaluator $evaluator): JsonResponse
    {
        $segment = AudienceSegment::query()->where('key', $key)->firstOrFail();
        $this->authorize('view', $segment);

        $ids = $evaluator->memberIds($segment);

        return response()->json([
            'data' => [
                'key' => $segment->key,
                'count' => count($ids),
                'user_ids' => $ids,
            ],
        ]);
    }
}
