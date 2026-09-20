<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CreateClinicalTreatmentSessionAction;
use App\Actions\Clinical\UpdateClinicalTreatmentSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalTreatmentSessionRequest;
use App\Http\Requests\Api\V1\UpdateClinicalTreatmentSessionRequest;
use App\Http\Resources\ClinicalTreatmentSessionResource;
use App\Models\ClinicalTreatmentPlan;
use App\Models\ClinicalTreatmentSession;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicalTreatmentSessionController extends Controller
{
    public function index(Request $request, Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalTreatmentSession::class, $patient]);

        $query = $patient->treatmentSessions()->latest('session_at');

        if ($planId = $request->integer('treatment_plan_id')) {
            $query->where('treatment_plan_id', $planId);
        }

        return ClinicalTreatmentSessionResource::collection($query->paginate());
    }

    public function indexForPlan(Patient $patient, ClinicalTreatmentPlan $treatmentPlan): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalTreatmentSession::class, $patient]);
        $this->authorize('view', $treatmentPlan);

        return ClinicalTreatmentSessionResource::collection(
            $patient->treatmentSessions()
                ->where('treatment_plan_id', $treatmentPlan->id)
                ->latest('session_at')
                ->paginate()
        );
    }

    public function store(
        StoreClinicalTreatmentSessionRequest $request,
        Patient $patient,
        CreateClinicalTreatmentSessionAction $action,
    ): JsonResponse {
        $session = $action->handle($patient, $request->validated());

        return (new ClinicalTreatmentSessionResource($session))
            ->response()
            ->setStatusCode(201);
    }

    public function storeForPlan(
        StoreClinicalTreatmentSessionRequest $request,
        Patient $patient,
        ClinicalTreatmentPlan $treatmentPlan,
        CreateClinicalTreatmentSessionAction $action,
    ): JsonResponse {
        $this->authorize('view', $treatmentPlan);

        $data = $request->validated();
        $data['treatment_plan_id'] = $treatmentPlan->id;

        $session = $action->handle($patient, $data);

        return (new ClinicalTreatmentSessionResource($session))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, ClinicalTreatmentSession $treatmentSession): ClinicalTreatmentSessionResource
    {
        $this->authorize('view', $treatmentSession);

        return new ClinicalTreatmentSessionResource($treatmentSession);
    }

    public function update(
        UpdateClinicalTreatmentSessionRequest $request,
        Patient $patient,
        ClinicalTreatmentSession $treatmentSession,
        UpdateClinicalTreatmentSessionAction $action,
    ): ClinicalTreatmentSessionResource {
        return new ClinicalTreatmentSessionResource(
            $action->handle($treatmentSession, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        ClinicalTreatmentSession $treatmentSession,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $treatmentSession);

        $auditLogger->log('clinical.treatment_session.deleted', $treatmentSession, [
            'patient_uuid' => $patient->uuid,
            'session_uuid' => $treatmentSession->uuid,
        ]);

        $treatmentSession->delete();

        return response()->noContent();
    }
}
