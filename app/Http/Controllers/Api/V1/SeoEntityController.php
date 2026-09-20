<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Seo\CreateSeoEntityAction;
use App\Actions\Seo\PublishSeoEntityAction;
use App\Actions\Seo\UpdateSeoEntityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Seo\StoreSeoEntityRequest;
use App\Http\Requests\Api\V1\Seo\UpdateSeoEntityRequest;
use App\Http\Resources\SeoEntityResource;
use App\Models\SeoEntity;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SeoEntityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SeoEntity::class);

        $tenantId = TenantContext::id();
        $canManage = $request->user()?->isSuperAdmin()
            || $request->user()?->hasPermission('seo.manage');

        $query = SeoEntity::query()
            ->visibleToTenant($tenantId)
            ->latest();

        if (! $canManage) {
            $query->published()->whereNotNull('last_reviewed_at');
        } elseif ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('locale')) {
            $query->where('locale', $request->string('locale')->toString());
        }

        return SeoEntityResource::collection($query->paginate());
    }

    public function store(
        StoreSeoEntityRequest $request,
        CreateSeoEntityAction $action,
    ): JsonResponse {
        $entity = $action->handle($request->validated());

        return (new SeoEntityResource($entity))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SeoEntity $entity): SeoEntityResource
    {
        $this->assertVisible($entity);
        $this->authorize('view', $entity);

        return new SeoEntityResource(
            $entity->load(['relatedEntities', 'faqs', 'author', 'reviewer'])
        );
    }

    public function update(
        UpdateSeoEntityRequest $request,
        SeoEntity $entity,
        UpdateSeoEntityAction $action,
    ): SeoEntityResource {
        $this->assertVisible($entity);

        return new SeoEntityResource($action->handle($entity, $request->validated()));
    }

    public function destroy(SeoEntity $entity, AuditLogger $auditLogger): Response
    {
        $this->assertVisible($entity);
        $this->authorize('delete', $entity);

        $auditLogger->log('seo.entity.deleted', $entity, [
            'entity_uuid' => $entity->uuid,
        ]);

        $entity->delete();

        return response()->noContent();
    }

    public function publish(
        SeoEntity $entity,
        PublishSeoEntityAction $action,
    ): SeoEntityResource {
        $this->assertVisible($entity);
        $this->authorize('publish', $entity);

        return new SeoEntityResource($action->handle($entity));
    }

    private function assertVisible(SeoEntity $entity): void
    {
        $user = request()->user();
        $tenantId = TenantContext::id();

        if (
            $entity->tenant_id !== null
            && $entity->tenant_id !== $tenantId
            && ! $user?->isSuperAdmin()
        ) {
            abort(404);
        }
    }
}
