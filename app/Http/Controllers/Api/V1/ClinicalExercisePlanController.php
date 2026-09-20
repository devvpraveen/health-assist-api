<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CreateClinicalExercisePlanAction;
use App\Actions\Clinical\SyncClinicalExercisePlanItemsAction;
use App\Actions\Clinical\UpdateClinicalExercisePlanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalExercisePlanRequest;
use App\Http\Requests\Api\V1\SyncClinicalExercisePlanItemsRequest;
use App\Http\Requests\Api\V1\UpdateClinicalExercisePlanRequest;
use App\Http\Resources\ClinicalExercisePlanResource;
use App\Models\ClinicalExercisePlan;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicalExercisePlanController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalExercisePlan::class, $patient]);

        return ClinicalExercisePlanResource::collection(
            $patient->exercisePlans()->with('items.exercise')->latest()->paginate()
        );
    }

    public function store(
        StoreClinicalExercisePlanRequest $request,
        Patient $patient,
        CreateClinicalExercisePlanAction $action,
    ): JsonResponse {
        $plan = $action->handle($patient, $request->validated());

        return (new ClinicalExercisePlanResource($plan->load('items.exercise')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, ClinicalExercisePlan $exercisePlan): ClinicalExercisePlanResource
    {
        $this->authorize('view', $exercisePlan);

        return new ClinicalExercisePlanResource($exercisePlan->load('items.exercise'));
    }

    public function update(
        UpdateClinicalExercisePlanRequest $request,
        Patient $patient,
        ClinicalExercisePlan $exercisePlan,
        UpdateClinicalExercisePlanAction $action,
    ): ClinicalExercisePlanResource {
        return new ClinicalExercisePlanResource(
            $action->handle($exercisePlan, $request->validated())->load('items.exercise')
        );
    }

    public function destroy(
        Patient $patient,
        ClinicalExercisePlan $exercisePlan,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $exercisePlan);

        $auditLogger->log('clinical.exercise_plan.deleted', $exercisePlan, [
            'patient_uuid' => $patient->uuid,
            'exercise_plan_uuid' => $exercisePlan->uuid,
        ]);

        $exercisePlan->delete();

        return response()->noContent();
    }

    public function syncItems(
        SyncClinicalExercisePlanItemsRequest $request,
        Patient $patient,
        ClinicalExercisePlan $exercisePlan,
        SyncClinicalExercisePlanItemsAction $action,
    ): ClinicalExercisePlanResource {
        return new ClinicalExercisePlanResource(
            $action->handle($exercisePlan, $request->validated())
        );
    }
}
