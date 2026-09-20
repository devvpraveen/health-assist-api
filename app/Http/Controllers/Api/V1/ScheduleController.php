<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Schedules\CreateScheduleAction;
use App\Actions\Schedules\UpdateScheduleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreScheduleRequest;
use App\Http\Requests\Api\V1\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Provider;
use App\Models\Schedule;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ScheduleController extends Controller
{
    public function index(Provider $provider): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Schedule::class);
        $this->authorize('view', $provider);

        return ScheduleResource::collection(
            $provider->schedules()->latest()->paginate()
        );
    }

    public function store(
        StoreScheduleRequest $request,
        Provider $provider,
        CreateScheduleAction $action,
    ): JsonResponse {
        $data = $request->validated();
        $data['provider_id'] = $provider->id;
        $data['clinic_id'] = $data['clinic_id'] ?? $provider->clinic_id;

        $schedule = $action->handle($data);

        return (new ScheduleResource($schedule))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Provider $provider, Schedule $schedule): ScheduleResource
    {
        $this->authorize('view', $schedule);
        abort_unless($schedule->provider_id === $provider->id, 404);

        return new ScheduleResource($schedule);
    }

    public function update(
        UpdateScheduleRequest $request,
        Provider $provider,
        Schedule $schedule,
        UpdateScheduleAction $action,
    ): ScheduleResource {
        abort_unless($schedule->provider_id === $provider->id, 404);

        return new ScheduleResource($action->handle($schedule, $request->validated()));
    }

    public function destroy(Provider $provider, Schedule $schedule, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $schedule);
        abort_unless($schedule->provider_id === $provider->id, 404);

        $auditLogger->log('schedule.deleted', $schedule, [
            'schedule_uuid' => $schedule->uuid,
        ]);

        $schedule->delete();

        return response()->noContent();
    }
}
