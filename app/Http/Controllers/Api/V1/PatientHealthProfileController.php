<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Patients\UpsertHealthProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateHealthProfileRequest;
use App\Http\Resources\PatientHealthProfileResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PatientHealthProfileController extends Controller
{
    public function show(Patient $patient): PatientHealthProfileResource|Response
    {
        $this->authorize('viewHealthProfile', $patient);

        $profile = $patient->healthProfile;

        if ($profile === null) {
            return response()->noContent();
        }

        return new PatientHealthProfileResource($profile);
    }

    public function update(
        UpdateHealthProfileRequest $request,
        Patient $patient,
        UpsertHealthProfileAction $action,
    ): JsonResponse {
        $profile = $action->handle($patient, $request->validated());

        return (new PatientHealthProfileResource($profile))
            ->response()
            ->setStatusCode($profile->wasRecentlyCreated ? 201 : 200);
    }
}
