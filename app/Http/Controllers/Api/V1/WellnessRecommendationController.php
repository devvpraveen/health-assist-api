<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WellnessRecommendationResource;
use App\Models\Patient;
use App\Models\PatientWellnessPreference;
use App\Services\Wellness\WellnessRecommendationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WellnessRecommendationController extends Controller
{
    public function index(
        Request $request,
        Patient $patient,
        WellnessRecommendationEngine $engine,
    ): JsonResponse {
        $this->authorize('view', [PatientWellnessPreference::class, $patient]);

        $limit = (int) $request->integer('limit', 10);
        $ranked = $engine->recommend($patient, $limit);

        return response()->json([
            'data' => WellnessRecommendationResource::collection(collect($ranked)),
            'disclaimer' => config('wellness.disclaimer'),
        ]);
    }
}
