<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Marketing\CaptureAttributionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketing\CaptureAttributionRequest;
use App\Http\Resources\AttributionTouchResource;
use App\Models\AttributionTouch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttributionController extends Controller
{
    public function storePublic(
        CaptureAttributionRequest $request,
        CaptureAttributionAction $action,
    ): JsonResponse {
        $touch = $action->handle($request->validated());

        return (new AttributionTouchResource($touch))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AttributionTouch::class);

        $query = AttributionTouch::query()->latest('captured_at');

        if ($request->filled('anonymous_id')) {
            $query->where('anonymous_id', $request->string('anonymous_id')->toString());
        }

        return AttributionTouchResource::collection($query->paginate());
    }
}
