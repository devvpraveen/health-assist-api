<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clinics\CreateClinicAction;
use App\Actions\Clinics\SyncClinicSpecialtiesAction;
use App\Actions\Clinics\UpdateClinicAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClinicRequest;
use App\Http\Requests\Api\V1\SyncClinicSpecialtiesRequest;
use App\Http\Requests\Api\V1\UpdateClinicRequest;
use App\Http\Resources\ClinicResource;
use App\Models\Clinic;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Clinic::class);

        $query = Clinic::query()->latest();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        return ClinicResource::collection($query->paginate());
    }

    public function store(StoreClinicRequest $request, CreateClinicAction $action): JsonResponse
    {
        $clinic = $action->handle($request->validated());

        return (new ClinicResource($clinic))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Clinic $clinic): ClinicResource
    {
        $this->authorize('view', $clinic);

        return new ClinicResource($clinic->load(['specialties', 'services', 'providers']));
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic, UpdateClinicAction $action): ClinicResource
    {
        return new ClinicResource($action->handle($clinic, $request->validated()));
    }

    public function destroy(Clinic $clinic, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $clinic);

        $auditLogger->log('clinic.deleted', $clinic, [
            'clinic_uuid' => $clinic->uuid,
        ]);

        $clinic->delete();

        return response()->noContent();
    }

    public function syncSpecialties(
        SyncClinicSpecialtiesRequest $request,
        Clinic $clinic,
        SyncClinicSpecialtiesAction $action,
    ): ClinicResource {
        return new ClinicResource(
            $action->handle($clinic, $request->validated('specialty_ids'))
        );
    }
}
