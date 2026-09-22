<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\EnsureTenantOrganizationAction;
use App\Actions\Organizations\UpdateOrganizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrganizationRequest;
use App\Http\Requests\Api\V1\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OrganizationController extends Controller
{
    public function index(Request $request, EnsureTenantOrganizationAction $ensure): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Organization::class);

        $user = $request->user();
        if ($user && ($user->hasPermission('organizations.manage') || $user->canViewClinicAppointmentBoard())) {
            $ensure->handle($user);
        }

        return OrganizationResource::collection(
            Organization::query()->latest()->paginate()
        );
    }

    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $action): JsonResponse
    {
        $this->authorize('create', Organization::class);

        $organization = $action->handle($request->validated());

        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('view', $organization);

        return new OrganizationResource($organization->load('clinics'));
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        UpdateOrganizationAction $action,
    ): OrganizationResource {
        $this->authorize('update', $organization);

        return new OrganizationResource($action->handle($organization, $request->validated()));
    }

    public function destroy(Organization $organization): Response
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->noContent();
    }
}
