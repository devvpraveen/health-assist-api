<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CreateClinicalProgressNoteAction;
use App\Actions\Clinical\TransitionClinicalDocumentAction;
use App\Actions\Clinical\UpdateClinicalProgressNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalProgressNoteRequest;
use App\Http\Requests\Api\V1\TransitionClinicalDocumentRequest;
use App\Http\Requests\Api\V1\UpdateClinicalProgressNoteRequest;
use App\Http\Resources\ClinicalProgressNoteResource;
use App\Models\ClinicalProgressNote;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicalProgressNoteController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalProgressNote::class, $patient]);

        return ClinicalProgressNoteResource::collection(
            $patient->progressNotes()->latest('noted_at')->paginate()
        );
    }

    public function store(
        StoreClinicalProgressNoteRequest $request,
        Patient $patient,
        CreateClinicalProgressNoteAction $action,
    ): JsonResponse {
        $note = $action->handle($patient, $request->validated());

        return (new ClinicalProgressNoteResource($note))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, ClinicalProgressNote $progressNote): ClinicalProgressNoteResource
    {
        $this->authorize('view', $progressNote);

        return new ClinicalProgressNoteResource($progressNote);
    }

    public function update(
        UpdateClinicalProgressNoteRequest $request,
        Patient $patient,
        ClinicalProgressNote $progressNote,
        UpdateClinicalProgressNoteAction $action,
    ): ClinicalProgressNoteResource {
        return new ClinicalProgressNoteResource(
            $action->handle($progressNote, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        ClinicalProgressNote $progressNote,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $progressNote);

        $auditLogger->log('clinical.progress_note.deleted', $progressNote, [
            'patient_uuid' => $patient->uuid,
            'progress_note_uuid' => $progressNote->uuid,
        ]);

        $progressNote->delete();

        return response()->noContent();
    }

    public function transition(
        TransitionClinicalDocumentRequest $request,
        Patient $patient,
        ClinicalProgressNote $progressNote,
        TransitionClinicalDocumentAction $action,
    ): ClinicalProgressNoteResource {
        $this->authorize('transition', $progressNote);

        return new ClinicalProgressNoteResource(
            $action->handle(
                $progressNote,
                $patient,
                $request->validated(),
                'clinical.progress_note',
                'clinical.progress_note.transitioned',
            )
        );
    }
}
