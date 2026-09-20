<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Patients\CreateEmergencyContactAction;
use App\Actions\Patients\UpdateEmergencyContactAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmergencyContactRequest;
use App\Http\Requests\Api\V1\UpdateEmergencyContactRequest;
use App\Http\Resources\PatientEmergencyContactResource;
use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PatientEmergencyContactController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [PatientEmergencyContact::class, $patient]);

        return PatientEmergencyContactResource::collection(
            $patient->emergencyContacts()->latest()->get()
        );
    }

    public function store(
        StoreEmergencyContactRequest $request,
        Patient $patient,
        CreateEmergencyContactAction $action,
    ): JsonResponse {
        $contact = $action->handle($patient, $request->validated());

        return (new PatientEmergencyContactResource($contact))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, PatientEmergencyContact $emergencyContact): PatientEmergencyContactResource
    {
        $this->authorize('view', $emergencyContact);

        return new PatientEmergencyContactResource($emergencyContact);
    }

    public function update(
        UpdateEmergencyContactRequest $request,
        Patient $patient,
        PatientEmergencyContact $emergencyContact,
        UpdateEmergencyContactAction $action,
    ): PatientEmergencyContactResource {
        return new PatientEmergencyContactResource(
            $action->handle($emergencyContact, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        PatientEmergencyContact $emergencyContact,
        AuditLogger $auditLogger,
        PatientTimelineRecorder $timelineRecorder,
    ): Response {
        $this->authorize('delete', $emergencyContact);

        DB::transaction(function () use ($patient, $emergencyContact, $auditLogger, $timelineRecorder): void {
            $timelineRecorder->record(
                $patient,
                'contact.deleted',
                'Emergency contact deleted',
                subject: $emergencyContact,
                meta: [
                    'patient_uuid' => $patient->uuid,
                    'contact_uuid' => $emergencyContact->uuid,
                ],
            );

            $auditLogger->log('emergency_contact.deleted', $emergencyContact, [
                'patient_uuid' => $patient->uuid,
                'contact_uuid' => $emergencyContact->uuid,
            ]);

            $emergencyContact->delete();
        });

        return response()->noContent();
    }
}
