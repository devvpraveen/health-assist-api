<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Providers\CreateProviderAction;
use App\Actions\Providers\SyncProviderBranchesAction;
use App\Actions\Providers\SyncProviderSpecialtiesAction;
use App\Actions\Providers\UpdateProviderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProviderRequest;
use App\Http\Requests\Api\V1\SyncProviderBranchesRequest;
use App\Http\Requests\Api\V1\SyncProviderSpecialtiesRequest;
use App\Http\Requests\Api\V1\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProviderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Provider::class);

        $query = Provider::query()->with(['specialties', 'clinic', 'tenant'])->latest();

        if ($clinicId = $request->integer('clinic_id')) {
            $query->where('clinic_id', $clinicId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        return ProviderResource::collection($query->paginate(
            min(200, max(1, (int) $request->integer('per_page', 50)))
        ));
    }

    public function store(StoreProviderRequest $request, CreateProviderAction $action): JsonResponse
    {
        $provider = $action->handle($request->validated());

        return (new ProviderResource($provider->load(['specialties', 'clinic', 'tenant'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Provider $provider): ProviderResource
    {
        $this->authorize('view', $provider);

        return new ProviderResource(
            $provider->load(['specialties', 'branches', 'schedules', 'clinic', 'tenant'])
        );
    }

    public function update(
        UpdateProviderRequest $request,
        Provider $provider,
        UpdateProviderAction $action,
    ): ProviderResource {
        return new ProviderResource(
            $action->handle($provider, $request->validated())->load(['specialties', 'clinic', 'tenant'])
        );
    }

    public function destroy(Provider $provider, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $provider);

        $auditLogger->log('provider.deleted', $provider, [
            'provider_uuid' => $provider->uuid,
        ]);

        $provider->delete();

        return response()->noContent();
    }

    public function syncSpecialties(
        SyncProviderSpecialtiesRequest $request,
        Provider $provider,
        SyncProviderSpecialtiesAction $action,
    ): ProviderResource {
        $payload = $request->validated();
        $specialties = $payload['specialties'] ?? $payload['specialty_ids'];

        return new ProviderResource($action->handle($provider, $specialties));
    }

    public function syncBranches(
        SyncProviderBranchesRequest $request,
        Provider $provider,
        SyncProviderBranchesAction $action,
    ): ProviderResource {
        return new ProviderResource(
            $action->handle($provider, $request->validated('branch_ids'))
        );
    }
}
