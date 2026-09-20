<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CreateClinicalAssessmentAction;
use App\Actions\Clinical\TransitionClinicalDocumentAction;
use App\Actions\Clinical\UpdateClinicalAssessmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalAssessmentRequest;
use App\Http\Requests\Api\V1\TransitionClinicalDocumentRequest;
use App\Http\Requests\Api\V1\UpdateClinicalAssessmentRequest;
use App\Http\Resources\ClinicalAssessmentResource;
use App\Models\ClinicalAssessment;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicalAssessmentController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalAssessment::class, $patient]);

        return ClinicalAssessmentResource::collection(
            $patient->assessments()->latest('assessed_at')->paginate()
        );
    }

    public function store(
        StoreClinicalAssessmentRequest $request,
        Patient $patient,
        CreateClinicalAssessmentAction $action,
    ): JsonResponse {
        $assessment = $action->handle($patient, $request->validated());

        return (new ClinicalAssessmentResource($assessment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, ClinicalAssessment $assessment): ClinicalAssessmentResource
    {
        $this->authorize('view', $assessment);

        return new ClinicalAssessmentResource($assessment);
    }

    public function update(
        UpdateClinicalAssessmentRequest $request,
        Patient $patient,
        ClinicalAssessment $assessment,
        UpdateClinicalAssessmentAction $action,
    ): ClinicalAssessmentResource {
        return new ClinicalAssessmentResource(
            $action->handle($assessment, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        ClinicalAssessment $assessment,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $assessment);

        $auditLogger->log('clinical.assessment.deleted', $assessment, [
            'patient_uuid' => $patient->uuid,
            'assessment_uuid' => $assessment->uuid,
        ]);

        $assessment->delete();

        return response()->noContent();
    }

    public function transition(
        TransitionClinicalDocumentRequest $request,
        Patient $patient,
        ClinicalAssessment $assessment,
        TransitionClinicalDocumentAction $action,
    ): ClinicalAssessmentResource {
        $this->authorize('transition', $assessment);

        return new ClinicalAssessmentResource(
            $action->handle(
                $assessment,
                $patient,
                $request->validated(),
                'clinical.assessment',
                'clinical.assessment.transitioned',
            )
        );
    }
}
