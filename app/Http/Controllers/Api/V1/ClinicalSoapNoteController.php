<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CreateClinicalSoapNoteAction;
use App\Actions\Clinical\TransitionClinicalDocumentAction;
use App\Actions\Clinical\UpdateClinicalSoapNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicalSoapNoteRequest;
use App\Http\Requests\Api\V1\TransitionClinicalDocumentRequest;
use App\Http\Requests\Api\V1\UpdateClinicalSoapNoteRequest;
use App\Http\Resources\ClinicalSoapNoteResource;
use App\Models\ClinicalSoapNote;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicalSoapNoteController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [ClinicalSoapNote::class, $patient]);

        return ClinicalSoapNoteResource::collection(
            $patient->soapNotes()->latest('session_date')->paginate()
        );
    }

    public function store(
        StoreClinicalSoapNoteRequest $request,
        Patient $patient,
        CreateClinicalSoapNoteAction $action,
    ): JsonResponse {
        $note = $action->handle($patient, $request->validated());

        return (new ClinicalSoapNoteResource($note))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, ClinicalSoapNote $soapNote): ClinicalSoapNoteResource
    {
        $this->authorize('view', $soapNote);

        return new ClinicalSoapNoteResource($soapNote);
    }

    public function update(
        UpdateClinicalSoapNoteRequest $request,
        Patient $patient,
        ClinicalSoapNote $soapNote,
        UpdateClinicalSoapNoteAction $action,
    ): ClinicalSoapNoteResource {
        return new ClinicalSoapNoteResource(
            $action->handle($soapNote, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        ClinicalSoapNote $soapNote,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $soapNote);

        $auditLogger->log('clinical.soap_note.deleted', $soapNote, [
            'patient_uuid' => $patient->uuid,
            'soap_note_uuid' => $soapNote->uuid,
        ]);

        $soapNote->delete();

        return response()->noContent();
    }

    public function transition(
        TransitionClinicalDocumentRequest $request,
        Patient $patient,
        ClinicalSoapNote $soapNote,
        TransitionClinicalDocumentAction $action,
    ): ClinicalSoapNoteResource {
        $this->authorize('transition', $soapNote);

        return new ClinicalSoapNoteResource(
            $action->handle(
                $soapNote,
                $patient,
                $request->validated(),
                'clinical.soap_note',
                'clinical.soap_note.transitioned',
            )
        );
    }
}
