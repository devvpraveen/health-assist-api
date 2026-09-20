<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Specialties\CreateSpecialtyAction;
use App\Actions\Specialties\UpdateSpecialtyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSpecialtyRequest;
use App\Http\Requests\Api\V1\UpdateSpecialtyRequest;
use App\Http\Resources\SpecialtyResource;
use App\Models\Specialty;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class SpecialtyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Specialty::class);

        $specialties = Specialty::query()
            ->visibleToTenant(TenantContext::id())
            ->orderBy('name')
            ->paginate();

        return SpecialtyResource::collection($specialties);
    }

    public function store(StoreSpecialtyRequest $request, CreateSpecialtyAction $action): JsonResponse
    {
        $specialty = $action->handle($request->validated());

        return (new SpecialtyResource($specialty))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Specialty $specialty): SpecialtyResource
    {
        $this->authorize('view', $specialty);

        return new SpecialtyResource($specialty);
    }

    public function update(
        UpdateSpecialtyRequest $request,
        Specialty $specialty,
        UpdateSpecialtyAction $action,
    ): SpecialtyResource {
        return new SpecialtyResource($action->handle($specialty, $request->validated()));
    }

    public function destroy(Specialty $specialty, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $specialty);

        if ($specialty->tenant_id === null) {
            throw ValidationException::withMessages([
                'specialty' => ['System specialties cannot be deleted.'],
            ]);
        }

        $auditLogger->log('specialty.deleted', $specialty, [
            'specialty_uuid' => $specialty->uuid,
        ]);

        $specialty->delete();

        return response()->noContent();
    }
}
