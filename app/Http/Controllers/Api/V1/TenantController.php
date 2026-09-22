<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Tenants\CreateTenantAction;
use App\Actions\Tenants\ProvisionTenantOrganizationAction;
use App\Actions\Tenants\UpdateTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProvisionTenantOrganizationRequest;
use App\Http\Requests\Api\V1\StoreTenantRequest;
use App\Http\Requests\Api\V1\UpdateTenantRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TenantController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Tenant::class);

        $perPage = min(500, max(1, (int) request()->integer('per_page', 100)));

        return TenantResource::collection(
            Tenant::query()->latest()->paginate($perPage)
        );
    }

    public function store(StoreTenantRequest $request, CreateTenantAction $action): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        $tenant = $action->handle($request->validated());

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    public function provision(
        ProvisionTenantOrganizationRequest $request,
        ProvisionTenantOrganizationAction $action,
    ): JsonResponse {
        $this->authorize('create', Tenant::class);

        $result = $action->handle($request->validated());

        return response()->json([
            'data' => [
                'tenant' => new TenantResource($result['tenant']),
                'organization' => new OrganizationResource($result['organization']),
            ],
            'message' => 'Tenant and organization provisioned.',
        ], 201);
    }

    public function show(Tenant $tenant): TenantResource
    {
        $this->authorize('view', $tenant);

        return new TenantResource($tenant);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant, UpdateTenantAction $action): TenantResource
    {
        $this->authorize('update', $tenant);

        return new TenantResource($action->handle($tenant, $request->validated()));
    }

    public function destroy(Tenant $tenant): Response
    {
        $this->authorize('delete', $tenant);

        $tenant->delete();

        return response()->noContent();
    }
}
