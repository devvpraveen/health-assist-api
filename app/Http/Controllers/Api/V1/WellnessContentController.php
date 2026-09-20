<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Wellness\CreateWellnessContentAction;
use App\Actions\Wellness\UpdateWellnessContentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wellness\StoreWellnessContentRequest;
use App\Http\Requests\Api\V1\Wellness\UpdateWellnessContentRequest;
use App\Http\Resources\WellnessContentResource;
use App\Models\WellnessContent;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WellnessContentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WellnessContent::class);

        $tenantId = TenantContext::id();
        $canManage = $request->user()?->isSuperAdmin()
            || $request->user()?->hasPermission('wellness.manage');

        $query = WellnessContent::query()
            ->visibleToTenant($tenantId)
            ->with('category')
            ->latest();

        if (! $canManage) {
            $query->published()->wellnessOnly();
        } elseif ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')->toString()));
        }

        return WellnessContentResource::collection($query->paginate());
    }

    public function store(
        StoreWellnessContentRequest $request,
        CreateWellnessContentAction $action,
    ): JsonResponse {
        $content = $action->handle($request->validated());

        return (new WellnessContentResource($content))
            ->response()
            ->setStatusCode(201);
    }

    public function show(WellnessContent $content): WellnessContentResource
    {
        $user = request()->user();
        $tenantId = TenantContext::id();

        if (
            $content->tenant_id !== null
            && $content->tenant_id !== $tenantId
            && ! $user?->isSuperAdmin()
        ) {
            abort(404);
        }

        $this->authorize('view', $content);

        $canManage = $user?->isSuperAdmin() || $user?->hasPermission('wellness.manage');

        if (! $canManage && ($content->status !== WellnessContent::STATUS_PUBLISHED || $content->is_clinical_advice)) {
            abort(404);
        }

        return new WellnessContentResource($content->load('category'));
    }

    public function update(
        UpdateWellnessContentRequest $request,
        WellnessContent $content,
        UpdateWellnessContentAction $action,
    ): WellnessContentResource {
        return new WellnessContentResource(
            $action->handle($content, $request->validated())
        );
    }

    public function destroy(WellnessContent $content, AuditLogger $auditLogger): Response
    {
        $this->authorize('delete', $content);

        $auditLogger->log('wellness.content.deleted', $content, [
            'content_uuid' => $content->uuid,
        ]);

        $content->delete();

        return response()->noContent();
    }
}
