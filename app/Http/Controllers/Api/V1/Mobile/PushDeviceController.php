<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Actions\Mobile\RegisterPushDeviceAction;
use App\Actions\Mobile\UnregisterPushDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Mobile\StorePushDeviceRequest;
use App\Http\Resources\PushDeviceResource;
use App\Models\PushDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PushDeviceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', PushDevice::class);

        $devices = PushDevice::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->get();

        return PushDeviceResource::collection($devices);
    }

    public function store(
        StorePushDeviceRequest $request,
        RegisterPushDeviceAction $action,
    ): JsonResponse {
        $device = $action->handle($request->user(), $request->validated());

        return (new PushDeviceResource($device))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, string $device, UnregisterPushDeviceAction $action): Response
    {
        $pushDevice = $this->resolveDevice($request, $device);

        Gate::authorize('delete', $pushDevice);

        $action->handle($pushDevice);

        return response()->noContent();
    }

    private function resolveDevice(Request $request, string $device): PushDevice
    {
        $query = PushDevice::query()->where('user_id', $request->user()->id);

        $found = (clone $query)
            ->where(function ($q) use ($device): void {
                $q->where('uuid', $device)
                    ->orWhere('token', $device);

                if (ctype_digit($device)) {
                    $q->orWhere('id', (int) $device);
                }
            })
            ->first();

        if ($found === null) {
            abort(404);
        }

        return $found;
    }
}
