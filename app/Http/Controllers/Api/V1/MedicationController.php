<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Medications\CreateMedicationAction;
use App\Actions\Medications\UpdateMedicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medications\StoreMedicationRequest;
use App\Http\Requests\Api\V1\Medications\UpdateMedicationRequest;
use App\Http\Resources\MedicationResource;
use App\Models\Medication;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\Medications\MedicationAdherenceService;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MedicationController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Medication::class, $patient]);

        return MedicationResource::collection(
            $patient->medications()->with('schedules')->latest()->paginate()
        );
    }

    public function store(
        StoreMedicationRequest $request,
        Patient $patient,
        CreateMedicationAction $action,
    ): JsonResponse {
        $medication = $action->handle($patient, $request->validated());

        return (new MedicationResource($medication->load('schedules')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, Medication $medication): MedicationResource
    {
        $this->authorize('view', $medication);

        return new MedicationResource($medication->load('schedules'));
    }

    public function update(
        UpdateMedicationRequest $request,
        Patient $patient,
        Medication $medication,
        UpdateMedicationAction $action,
    ): MedicationResource {
        return new MedicationResource(
            $action->handle($medication, $request->validated())->load('schedules')
        );
    }

    public function destroy(
        Patient $patient,
        Medication $medication,
        AuditLogger $auditLogger,
        PatientTimelineRecorder $timelineRecorder,
    ): Response {
        $this->authorize('delete', $medication);

        $auditLogger->log('medication.deleted', $medication, [
            'patient_uuid' => $patient->uuid,
            'medication_uuid' => $medication->uuid,
        ]);

        $timelineRecorder->record(
            $patient,
            'medication.deleted',
            'Medication removed',
            subject: $medication,
            meta: [
                'patient_uuid' => $patient->uuid,
                'medication_uuid' => $medication->uuid,
            ],
        );

        $medication->delete();

        return response()->noContent();
    }

    public function adherence(
        Patient $patient,
        Medication $medication,
        MedicationAdherenceService $adherenceService,
    ): JsonResponse {
        $this->authorize('view', $medication);

        return response()->json([
            'data' => $adherenceService->summarize($medication),
            'disclaimer' => config('medication.disclaimer'),
        ]);
    }
}
