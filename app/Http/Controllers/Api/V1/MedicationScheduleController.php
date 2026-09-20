<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Medications\CreateMedicationScheduleAction;
use App\Actions\Medications\UpdateMedicationScheduleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medications\StoreMedicationScheduleRequest;
use App\Http\Requests\Api\V1\Medications\UpdateMedicationScheduleRequest;
use App\Http\Resources\MedicationScheduleResource;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class MedicationScheduleController extends Controller
{
    public function index(Patient $patient, Medication $medication): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [MedicationSchedule::class, $medication]);

        return MedicationScheduleResource::collection(
            $medication->schedules()->latest()->paginate()
        );
    }

    public function store(
        StoreMedicationScheduleRequest $request,
        Patient $patient,
        Medication $medication,
        CreateMedicationScheduleAction $action,
    ): JsonResponse {
        $schedule = $action->handle($medication, $request->validated());

        return (new MedicationScheduleResource($schedule))
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        Patient $patient,
        Medication $medication,
        MedicationSchedule $schedule,
    ): MedicationScheduleResource {
        $this->authorize('view', $schedule);

        return new MedicationScheduleResource($schedule);
    }

    public function update(
        UpdateMedicationScheduleRequest $request,
        Patient $patient,
        Medication $medication,
        MedicationSchedule $schedule,
        UpdateMedicationScheduleAction $action,
    ): MedicationScheduleResource {
        return new MedicationScheduleResource(
            $action->handle($schedule, $request->validated())
        );
    }

    public function destroy(
        Patient $patient,
        Medication $medication,
        MedicationSchedule $schedule,
        AuditLogger $auditLogger,
    ): Response {
        $this->authorize('delete', $schedule);

        $auditLogger->log('medication.schedule.deleted', $schedule, [
            'medication_uuid' => $medication->uuid,
            'schedule_uuid' => $schedule->uuid,
        ]);

        $schedule->delete();

        return response()->noContent();
    }
}
