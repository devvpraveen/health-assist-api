<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Medications\LogMedicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Medications\StoreMedicationLogRequest;
use App\Http\Resources\MedicationLogResource;
use App\Models\Medication;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicationLogController extends Controller
{
    public function index(Patient $patient, Medication $medication): AnonymousResourceCollection
    {
        $this->authorize('view', $medication);

        return MedicationLogResource::collection(
            $medication->logs()->latest('logged_at')->paginate()
        );
    }

    public function store(
        StoreMedicationLogRequest $request,
        Patient $patient,
        Medication $medication,
        LogMedicationAction $action,
    ): JsonResponse {
        $log = $action->handle($medication, $request->validated());

        return (new MedicationLogResource($log))
            ->response()
            ->setStatusCode(201);
    }
}
