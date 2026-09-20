<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Seo\CreateSeoFaqAction;
use App\Actions\Seo\UpdateSeoFaqAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Seo\StoreSeoFaqRequest;
use App\Http\Requests\Api\V1\Seo\UpdateSeoFaqRequest;
use App\Http\Resources\SeoFaqResource;
use App\Models\SeoFaq;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SeoFaqController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', SeoFaq::class);

        $tenantId = TenantContext::id();

        $query = SeoFaq::query()
            ->visibleToTenant($tenantId)
            ->with('entity')
            ->orderBy('sort_order')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('locale')) {
            $query->where('locale', $request->string('locale')->toString());
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->integer('entity_id'));
        }

        return SeoFaqResource::collection($query->paginate());
    }

    public function store(
        StoreSeoFaqRequest $request,
        CreateSeoFaqAction $action,
    ): JsonResponse {
        $faq = $action->handle($request->validated());

        return (new SeoFaqResource($faq))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SeoFaq $faq): SeoFaqResource
    {
        $this->assertVisible($faq);
        $this->authorize('view', $faq);

        return new SeoFaqResource($faq->load('entity'));
    }

    public function update(
        UpdateSeoFaqRequest $request,
        SeoFaq $faq,
        UpdateSeoFaqAction $action,
    ): SeoFaqResource {
        $this->assertVisible($faq);

        return new SeoFaqResource($action->handle($faq, $request->validated()));
    }

    public function destroy(SeoFaq $faq, AuditLogger $auditLogger): Response
    {
        $this->assertVisible($faq);
        $this->authorize('delete', $faq);

        $auditLogger->log('seo.faq.deleted', $faq, [
            'faq_uuid' => $faq->uuid,
        ]);

        $faq->delete();

        return response()->noContent();
    }

    private function assertVisible(SeoFaq $faq): void
    {
        $user = request()->user();
        $tenantId = TenantContext::id();

        if (
            $faq->tenant_id !== null
            && $faq->tenant_id !== $tenantId
            && ! $user?->isSuperAdmin()
        ) {
            abort(404);
        }
    }
}
