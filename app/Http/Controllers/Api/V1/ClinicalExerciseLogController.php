<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\LogClinicalExerciseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalExerciseLogRequest;
use App\Http\Resources\ClinicalExerciseLogResource;
use App\Models\ClinicalExerciseLog;
use App\Models\ClinicalExercisePlan;
use App\Models\ClinicalExercisePlanItem;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClinicalExerciseLogController extends Controller
{
    public function index(Patient $patient, ClinicalExercisePlan $exercisePlan): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalExerciseLog::class, $patient]);
        $this->authorize('view', $exercisePlan);

        $logs = ClinicalExerciseLog::query()
            ->where('patient_id', $patient->id)
            ->whereIn('exercise_plan_item_id', $exercisePlan->items()->pluck('id'))
            ->latest('performed_at')
            ->paginate();

        return ClinicalExerciseLogResource::collection($logs);
    }

    public function store(
        StoreClinicalExerciseLogRequest $request,
        Patient $patient,
        ClinicalExercisePlan $exercisePlan,
        ClinicalExercisePlanItem $item,
        LogClinicalExerciseAction $action,
    ): JsonResponse {
        $log = $action->handle($exercisePlan, $item, $request->validated());

        return (new ClinicalExerciseLogResource($log))
            ->response()
            ->setStatusCode(201);
    }
}
