<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Marketing\TrackAnalyticsEventAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketing\TrackAnalyticsEventRequest;
use Illuminate\Http\JsonResponse;

class AnalyticsEventController extends Controller
{
    public function storePublic(
        TrackAnalyticsEventRequest $request,
        TrackAnalyticsEventAction $action,
    ): JsonResponse {
        $action->handle($request->validated());

        return response()->json(['data' => ['tracked' => true]], 202);
    }

    public function store(
        TrackAnalyticsEventRequest $request,
        TrackAnalyticsEventAction $action,
    ): JsonResponse {
        $action->handle($request->validated(), $request->user());

        return response()->json(['data' => ['tracked' => true]], 202);
    }
}
