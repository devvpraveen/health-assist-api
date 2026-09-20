<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Patients\DeletePatientDocumentAction;
use App\Actions\Patients\UploadPatientDocumentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePatientDocumentRequest;
use App\Http\Resources\PatientDocumentResource;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientDocumentController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [PatientDocument::class, $patient]);

        return PatientDocumentResource::collection(
            $patient->documents()->latest()->paginate()
        );
    }

    public function store(
        StorePatientDocumentRequest $request,
        Patient $patient,
        UploadPatientDocumentAction $action,
    ): JsonResponse {
        $document = $action->handle(
            $patient,
            $request->file('file'),
            $request->safe()->except('file'),
            $request->user(),
        );

        return (new PatientDocumentResource($document))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, PatientDocument $document): PatientDocumentResource
    {
        $this->authorize('view', $document);

        return new PatientDocumentResource($document);
    }

    public function download(
        Patient $patient,
        PatientDocument $document,
        AuditLogger $auditLogger,
    ): StreamedResponse {
        $this->authorize('download', $document);

        $auditLogger->log('document.downloaded', $document, [
            'patient_uuid' => $patient->uuid,
            'document_uuid' => $document->uuid,
        ]);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type],
        );
    }

    public function destroy(
        Patient $patient,
        PatientDocument $document,
        DeletePatientDocumentAction $action,
    ): Response {
        $this->authorize('delete', $document);

        $action->handle($document);

        return response()->noContent();
    }
}
