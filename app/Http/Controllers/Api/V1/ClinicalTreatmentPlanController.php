<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CompleteTreatmentPlanAction;
use App\Actions\Clinical\CreateClinicalTreatmentPlanAction;
use App\Actions\Clinical\TransitionClinicalDocumentAction;
use App\Actions\Clinical\UpdateClinicalTreatmentPlanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalTreatmentPlanRequest;
use App\Http\Requests\Api\V1\TransitionClinicalDocumentRequest;
use App\Http\Requests\Api\V1\UpdateClinicalTreatmentPlanRequest;
use App\Http\Resources\ClinicalTreatmentPlanResource;
use App\Models\ClinicalTreatmentPlan;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicalTreatmentPlanController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalTreatmentPlan::class, $patient]);

        return ClinicalTreatmentPlanResource::collection(
            $patient->treatmentPlans()->latest()->paginate()
        );
    }

    public function store(
        StoreClinicalTreatmentPlanRequest $request,
        Patient $patient,
        CreateClinicalTreatmentPlanAction $action,
    ): JsonResponse {
        $plan = $action->handle($patient, $request->validated());

        return (new ClinicalTreatmentPlanResource($plan))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, ClinicalTreatmentPlan $treatmentPlan): ClinicalTreatmentPlanResource
    {
        $this->authorize('view', $treatmentPlan);

        return new ClinicalTreatmentPlanResource($treatmentPlan);
    }

    public function update(
        UpdateClinicalTreatmentPlanRequest $request,
        Patient $patient,
        ClinicalTreatmentPlan $treatmentPlan,
        UpdateClinicalTreatmentPlanAction $action,
    ): ClinicalTreatmentPlanResource {
        return new ClinicalTreatmentPlanResource(
            $action->handle($treatmentPlan, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        ClinicalTreatmentPlan $treatmentPlan,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $treatmentPlan);

        $auditLogger->log('clinical.treatment_plan.deleted', $treatmentPlan, [
            'patient_uuid' => $patient->uuid,
            'treatment_plan_uuid' => $treatmentPlan->uuid,
        ]);

        $treatmentPlan->delete();

        return response()->noContent();
    }

    public function transition(
        TransitionClinicalDocumentRequest $request,
        Patient $patient,
        ClinicalTreatmentPlan $treatmentPlan,
        TransitionClinicalDocumentAction $action,
    ): ClinicalTreatmentPlanResource {
        $this->authorize('transition', $treatmentPlan);

        return new ClinicalTreatmentPlanResource(
            $action->handle(
                $treatmentPlan,
                $patient,
                $request->validated(),
                'clinical.treatment_plan',
                'clinical.treatment_plan.transitioned',
            )
        );
    }

    public function complete(
        Patient $patient,
        ClinicalTreatmentPlan $treatmentPlan,
        CompleteTreatmentPlanAction $action,
    ): ClinicalTreatmentPlanResource {
        $this->authorize('transition', $treatmentPlan);

        return new ClinicalTreatmentPlanResource($action->handle($treatmentPlan));
    }
}
