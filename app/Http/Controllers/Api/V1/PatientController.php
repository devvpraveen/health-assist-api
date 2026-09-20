<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Patients\CreatePatientAction;
use App\Actions\Patients\UpdatePatientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePatientRequest;
use App\Http\Requests\Api\V1\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Patient::class);

        $query = Patient::query()->latest();

        // Linked patient accounts can list only their own profile(s).
        if (! $request->user()?->isSuperAdmin() && ! $request->user()?->hasPermission('patients.view')) {
            $query->where('user_id', $request->user()->id);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return PatientResource::collection($query->paginate());
    }

    public function store(StorePatientRequest $request, CreatePatientAction $action): JsonResponse
    {
        $patient = $action->handle($request->validated());

        return (new PatientResource($patient))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, AuditLogger $auditLogger): PatientResource
    {
        $this->authorize('view', $patient);

        $auditLogger->log('patient.viewed', $patient, [
            'patient_uuid' => $patient->uuid,
        ]);

        return new PatientResource($patient->load('healthProfile'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient, UpdatePatientAction $action): PatientResource
    {
        return new PatientResource($action->handle($patient, $request->validated()));
    }

    public function destroy(Patient $patient, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $patient);

        $auditLogger->log('patient.deleted', $patient, [
            'patient_uuid' => $patient->uuid,
        ]);

        $patient->delete();

        return response()->noContent();
    }
}
