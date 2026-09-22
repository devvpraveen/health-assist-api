<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Patients\CreatePatientAction;
use App\Actions\Patients\EnsureLinkedPatientAction;
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
    public function index(Request $request, EnsureLinkedPatientAction $ensureLinkedPatient): AnonymousResourceCollection
    {
        $user = $request->user();

        // OTP / patient-portal users need a chart before self-scoped listing & booking.
        if (
            $user
            && $user->tenant_id
            && ! $user->isSuperAdmin()
            && ! $user->hasPermission('patients.view')
        ) {
            $ensureLinkedPatient->handle($user);
        }

        $this->authorize('viewAny', Patient::class);

        $query = Patient::query()->with('tenant')->latest();

        // Linked patient accounts can list only their own profile(s).
        if (! $user?->isSuperAdmin() && ! $user?->hasPermission('patients.view')) {
            $query->where('user_id', $user->id);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return PatientResource::collection($query->paginate(
            min(200, max(1, (int) $request->integer('per_page', 50)))
        ));
    }

    public function store(StorePatientRequest $request, CreatePatientAction $action): JsonResponse
    {
        $patient = $action->handle($request->validated());

        return (new PatientResource($patient->load('tenant')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Patient $patient, AuditLogger $auditLogger): PatientResource
    {
        $this->authorize('view', $patient);

        $auditLogger->log('patient.viewed', $patient, [
            'patient_uuid' => $patient->uuid,
        ]);

        return new PatientResource($patient->load(['healthProfile', 'tenant']));
    }

    public function update(UpdatePatientRequest $request, Patient $patient, UpdatePatientAction $action): PatientResource
    {
        return new PatientResource(
            $action->handle($patient, $request->validated())->load('tenant')
        );
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
