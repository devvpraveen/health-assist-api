<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Marketing\CreateExperimentAction;
use App\Actions\Marketing\TrackAnalyticsEventAction;
use App\Actions\Marketing\UpdateExperimentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Marketing\AssignExperimentRequest;
use App\Http\Requests\Api\V1\Marketing\StoreExperimentRequest;
use App\Http\Requests\Api\V1\Marketing\UpdateExperimentRequest;
use App\Http\Resources\ExperimentAssignmentResource;
use App\Http\Resources\ExperimentResource;
use App\Models\Experiment;
use App\Services\AuditLogger;
use App\Services\Marketing\ExperimentAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ExperimentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Experiment::class);

        $query = Experiment::query()->with('variants')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return ExperimentResource::collection($query->paginate());
    }

    public function store(
        StoreExperimentRequest $request,
        CreateExperimentAction $action,
    ): JsonResponse {
        $experiment = $action->handle($request->validated());

        return (new ExperimentResource($experiment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Experiment $experiment): ExperimentResource
    {
        $this->authorize('view', $experiment);

        return new ExperimentResource($experiment->load('variants'));
    }

    public function update(
        UpdateExperimentRequest $request,
        Experiment $experiment,
        UpdateExperimentAction $action,
    ): ExperimentResource {
        return new ExperimentResource($action->handle($experiment, $request->validated()));
    }

    public function destroy(Experiment $experiment, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $experiment);

        $auditLogger->log('marketing.experiment.deleted', $experiment, [
            'key' => $experiment->key,
        ]);

        $experiment->delete();

        return response()->noContent();
    }

    public function assignPublic(
        string $key,
        AssignExperimentRequest $request,
        ExperimentAssignmentService $assignments,
    ): ExperimentAssignmentResource {
        $experiment = Experiment::query()
            ->withoutGlobalScope('tenant')
            ->where('key', $key)
            ->where('status', Experiment::STATUS_RUNNING)
            ->firstOrFail();

        $assignment = $assignments->assign(
            $experiment,
            $request->validated('anonymous_id'),
        );

        return new ExperimentAssignmentResource($assignment->load('experiment'));
    }

    public function expose(
        string $key,
        Request $request,
        TrackAnalyticsEventAction $track,
    ): JsonResponse {
        $experiment = Experiment::query()
            ->where('key', $key)
            ->firstOrFail();

        $this->authorize('expose', $experiment);

        $validated = $request->validate([
            'anonymous_id' => ['required', 'string', 'max:128'],
            'variant' => ['required', 'string', 'max:64'],
        ]);

        $track->handle([
            'name' => 'experiment_expose',
            'anonymous_id' => $validated['anonymous_id'],
            'properties' => [
                'experiment_key' => $experiment->key,
                'variant' => $validated['variant'],
            ],
            'tenant_id' => $experiment->tenant_id,
        ], $request->user());

        return response()->json(['data' => ['tracked' => true]], 202);
    }
}
