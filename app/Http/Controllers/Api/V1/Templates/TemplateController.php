<?php

namespace App\Http\Controllers\Api\V1\Templates;

use App\Actions\Templates\PublishTemplateVersionAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Templates\ContentTemplateResource;
use App\Http\Resources\Templates\TemplateVersionResource;
use App\Models\ContentTemplate;
use App\Models\TemplateVersion;
use App\Services\Templates\TemplateEngine;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->assertCan($request, 'templates.view');

        $tenantId = TenantContext::id();
        $ownerKeys = ['platform'];
        if ($tenantId !== null) {
            $ownerKeys[] = 'tenant:'.$tenantId;
        }

        $query = ContentTemplate::query()
            ->with('activeVersion')
            ->whereIn('owner_key', $ownerKeys)
            ->orderBy('type')
            ->orderBy('key');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }
        if ($request->filled('module_key')) {
            $query->where('module_key', $request->string('module_key')->toString());
        }
        if ($request->boolean('published_only')) {
            $query->where('status', ContentTemplate::STATUS_PUBLISHED);
        }

        return ContentTemplateResource::collection($query->paginate());
    }

    public function show(Request $request, ContentTemplate $template): ContentTemplateResource
    {
        $this->assertCan($request, 'templates.view');
        $this->assertVisible($template);

        $template->load(['activeVersion', 'versions']);

        return new ContentTemplateResource($template);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertCan($request, 'templates.manage');

        $tenantId = TenantContext::id();
        abort_if($tenantId === null, 403, 'Tenant context required.');

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'type' => ['required', 'string', 'max:50'],
            'module_key' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'schema' => ['required', 'array'],
            'label' => ['nullable', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
            'publish' => ['sometimes', 'boolean'],
        ]);

        $template = ContentTemplate::query()->create([
            'tenant_id' => $tenantId,
            'owner_key' => 'tenant:'.$tenantId,
            'key' => $data['key'],
            'type' => $data['type'],
            'module_key' => $data['module_key'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        $version = TemplateVersion::query()->create([
            'template_id' => $template->id,
            'version' => 1,
            'label' => $data['label'] ?? 'v1',
            'schema' => $data['schema'],
            'created_by_user_id' => $request->user()?->id,
        ]);

        if ($request->boolean('publish')) {
            app(PublishTemplateVersionAction::class)->handle($template, $version, $request->user());
            $template->refresh();
        }

        $template->load(['activeVersion', 'versions']);

        return (new ContentTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function storeVersion(Request $request, ContentTemplate $template): JsonResponse
    {
        $this->assertCan($request, 'templates.manage');
        $this->assertTenantOwned($template);

        $data = $request->validate([
            'schema' => ['required', 'array'],
            'label' => ['nullable', 'string', 'max:100'],
            'publish' => ['sometimes', 'boolean'],
        ]);

        $next = (int) $template->versions()->max('version') + 1;
        $version = TemplateVersion::query()->create([
            'template_id' => $template->id,
            'version' => $next,
            'label' => $data['label'] ?? 'v'.$next,
            'schema' => $data['schema'],
            'created_by_user_id' => $request->user()?->id,
        ]);

        if ($request->boolean('publish')) {
            app(PublishTemplateVersionAction::class)->handle($template, $version, $request->user());
        }

        return (new TemplateVersionResource($version->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function publish(
        Request $request,
        ContentTemplate $template,
        TemplateVersion $version,
        PublishTemplateVersionAction $action,
    ): TemplateVersionResource {
        $this->assertCan($request, 'templates.manage');
        $this->assertTenantOwned($template);
        abort_unless($version->template_id === $template->id, 404);

        return new TemplateVersionResource($action->handle($template, $version, $request->user()));
    }

    public function preview(Request $request, ContentTemplate $template, TemplateEngine $engine): JsonResponse
    {
        $this->assertCan($request, 'templates.view');
        $this->assertVisible($template);

        $data = $request->validate([
            'context' => ['nullable', 'array'],
            'version_id' => ['nullable', 'integer', Rule::exists('template_versions', 'id')->where('template_id', $template->id)],
        ]);

        $version = isset($data['version_id'])
            ? TemplateVersion::query()->whereKey($data['version_id'])->firstOrFail()
            : $template->activeVersion;

        abort_if($version === null, 422, 'No template version available to preview.');

        return response()->json([
            'data' => [
                'template_key' => $template->key,
                'version' => $version->version,
                'rendered' => $engine->render($version, $data['context'] ?? []),
            ],
        ]);
    }

    private function assertCan(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission($permission)),
            403,
        );
    }

    private function assertVisible(ContentTemplate $template): void
    {
        $tenantId = TenantContext::id();
        $allowed = $template->owner_key === 'platform'
            || ($tenantId !== null && $template->owner_key === 'tenant:'.$tenantId);
        abort_unless($allowed, 404);
    }

    private function assertTenantOwned(ContentTemplate $template): void
    {
        $tenantId = TenantContext::id();
        abort_unless(
            $tenantId !== null && $template->owner_key === 'tenant:'.$tenantId,
            403,
            'Only tenant-owned templates can be mutated here.',
        );
    }
}
