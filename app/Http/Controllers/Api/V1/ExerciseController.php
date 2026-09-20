<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinical\CreateExerciseAction;
use App\Actions\Clinical\UpdateExerciseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExerciseRequest;
use App\Http\Requests\Api\V1\UpdateExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ExerciseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Exercise::class);

        $exercises = Exercise::query()
            ->visibleToTenant(TenantContext::id())
            ->orderBy('name')
            ->paginate();

        return ExerciseResource::collection($exercises);
    }

    public function store(StoreExerciseRequest $request, CreateExerciseAction $action): JsonResponse
    {
        $exercise = $action->handle($request->validated());

        return (new ExerciseResource($exercise))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Exercise $exercise): ExerciseResource
    {
        $this->authorize('view', $exercise);

        return new ExerciseResource($exercise);
    }

    public function update(
        UpdateExerciseRequest $request,
        Exercise $exercise,
        UpdateExerciseAction $action,
    ): ExerciseResource {
        return new ExerciseResource($action->handle($exercise, $request->validated()));
    }

    public function destroy(Exercise $exercise, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $exercise);

        if ($exercise->tenant_id === null) {
            throw ValidationException::withMessages([
                'exercise' => ['System exercises cannot be deleted.'],
            ]);
        }

        $auditLogger->log('clinical.exercise.deleted', $exercise, [
            'exercise_uuid' => $exercise->uuid,
        ]);

        $exercise->delete();

        return response()->noContent();
    }
}
