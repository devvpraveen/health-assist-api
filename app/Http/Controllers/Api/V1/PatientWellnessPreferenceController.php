<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Wellness\UpsertPatientWellnessPreferencesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wellness\UpsertPatientWellnessPreferencesRequest;
use App\Http\Resources\PatientWellnessPreferenceResource;
use App\Models\Patient;
use App\Models\PatientWellnessPreference;
use Illuminate\Http\JsonResponse;

class PatientWellnessPreferenceController extends Controller
{
    public function show(Patient $patient): JsonResponse|PatientWellnessPreferenceResource
    {
        $this->authorize('view', [PatientWellnessPreference::class, $patient]);

        $prefs = PatientWellnessPreference::query()
            ->where('patient_id', $patient->id)
            ->first();

        if ($prefs === null) {
            return response()->json([
                'data' => [
                    'patient_id' => $patient->id,
                    'interests' => [],
                    'goals' => null,
                    'excluded_tags' => null,
                    'reminder_opt_in' => true,
                ],
            ]);
        }

        return new PatientWellnessPreferenceResource($prefs);
    }

    public function update(
        UpsertPatientWellnessPreferencesRequest $request,
        Patient $patient,
        UpsertPatientWellnessPreferencesAction $action,
    ): JsonResponse {
        return (new PatientWellnessPreferenceResource(
            $action->handle($patient, $request->validated())
        ))->response()->setStatusCode(200);
    }
}
