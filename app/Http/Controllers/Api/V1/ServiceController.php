<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Services\CreateServiceAction;
use App\Actions\Services\UpdateServiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServiceRequest;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Clinic;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    public function index(Clinic $clinic): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Service::class);
        $this->authorize('view', $clinic);

        return ServiceResource::collection(
            $clinic->services()->with('specialty')->latest()->paginate()
        );
    }

    public function store(
        StoreServiceRequest $request,
        Clinic $clinic,
        CreateServiceAction $action,
    ): JsonResponse {
        $data = $request->validated();
        $data['clinic_id'] = $clinic->id;

        $service = $action->handle($data);

        return (new ServiceResource($service->load('specialty')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Clinic $clinic, Service $service): ServiceResource
    {
        $this->authorize('view', $service);

        abort_unless($service->clinic_id === $clinic->id, 404);

        return new ServiceResource($service->load('specialty'));
    }

    public function update(
        UpdateServiceRequest $request,
        Clinic $clinic,
        Service $service,
        UpdateServiceAction $action,
    ): ServiceResource {
        abort_unless($service->clinic_id === $clinic->id, 404);

        return new ServiceResource(
            $action->handle($service, $request->validated())->load('specialty')
        );
    }

    public function destroy(Clinic $clinic, Service $service, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $service);
        abort_unless($service->clinic_id === $clinic->id, 404);

        $auditLogger->log('service.deleted', $service, [
            'service_uuid' => $service->uuid,
        ]);

        $service->delete();

        return response()->noContent();
    }
}
