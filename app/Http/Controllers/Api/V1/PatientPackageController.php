<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\ConsumePatientPackageSessionAction;
use App\Actions\Billing\CreatePatientPackageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ConsumePatientPackageRequest;
use App\Http\Requests\Api\V1\StorePatientPackageRequest;
use App\Http\Resources\PatientPackageResource;
use App\Models\Patient;
use App\Models\PatientPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PatientPackageController extends Controller
{
    public function index(Patient $patient): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [PatientPackage::class, $patient]);

        return PatientPackageResource::collection(
            $patient->patientPackages()->with('package')->latest('purchased_at')->paginate()
        );
    }

    public function store(
        StorePatientPackageRequest $request,
        Patient $patient,
        CreatePatientPackageAction $action,
    ): JsonResponse {
        $patientPackage = $action->handle($patient, $request->validated());

        return (new PatientPackageResource($patientPackage))
            ->response()
            ->setStatusCode(201);
    }

    public function consume(
        ConsumePatientPackageRequest $request,
        Patient $patient,
        PatientPackage $patientPackage,
        ConsumePatientPackageSessionAction $action,
    ): PatientPackageResource {
        $this->authorize('consume', $patientPackage);

        $sessions = (int) ($request->validated('sessions') ?? 1);

        return new PatientPackageResource(
            $action->handle($patient, $patientPackage, $sessions)
        );
    }
}
