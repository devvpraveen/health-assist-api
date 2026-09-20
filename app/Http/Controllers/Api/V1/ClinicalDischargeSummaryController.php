<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CreateClinicalDischargeSummaryAction;
use App\Actions\Clinical\TransitionClinicalDocumentAction;
use App\Actions\Clinical\UpdateClinicalDischargeSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalDischargeSummaryRequest;
use App\Http\Requests\Api\V1\TransitionClinicalDocumentRequest;
use App\Http\Requests\Api\V1\UpdateClinicalDischargeSummaryRequest;
use App\Http\Resources\ClinicalDischargeSummaryResource;
use App\Models\ClinicalDischargeSummary;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicalDischargeSummaryController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalDischargeSummary::class, $patient]);

        return ClinicalDischargeSummaryResource::collection(
            $patient->dischargeSummaries()->latest('discharged_at')->paginate()
        );
    }

    public function store(
        StoreClinicalDischargeSummaryRequest $request,
        Patient $patient,
        CreateClinicalDischargeSummaryAction $action,
    ): JsonResponse {
        $summary = $action->handle($patient, $request->validated());

        return (new ClinicalDischargeSummaryResource($summary))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, ClinicalDischargeSummary $dischargeSummary): ClinicalDischargeSummaryResource
    {
        $this->authorize('view', $dischargeSummary);

        return new ClinicalDischargeSummaryResource($dischargeSummary);
    }

    public function update(
        UpdateClinicalDischargeSummaryRequest $request,
        Patient $patient,
        ClinicalDischargeSummary $dischargeSummary,
        UpdateClinicalDischargeSummaryAction $action,
    ): ClinicalDischargeSummaryResource {
        return new ClinicalDischargeSummaryResource(
            $action->handle($dischargeSummary, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        ClinicalDischargeSummary $dischargeSummary,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $dischargeSummary);

        $auditLogger->log('clinical.discharge.deleted', $dischargeSummary, [
            'patient_uuid' => $patient->uuid,
            'discharge_uuid' => $dischargeSummary->uuid,
        ]);

        $dischargeSummary->delete();

        return response()->noContent();
    }

    public function transition(
        TransitionClinicalDocumentRequest $request,
        Patient $patient,
        ClinicalDischargeSummary $dischargeSummary,
        TransitionClinicalDocumentAction $action,
    ): ClinicalDischargeSummaryResource {
        $this->authorize('transition', $dischargeSummary);

        return new ClinicalDischargeSummaryResource(
            $action->handle(
                $dischargeSummary,
                $patient,
                $request->validated(),
                'clinical.discharge',
                'clinical.discharge.transitioned',
            )
        );
    }
}
