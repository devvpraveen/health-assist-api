<?php

namespace App\Http\Controllers\Api\V1\Forms;

use App\Actions\Forms\PublishFormVersionAction;
use App\Actions\Forms\SubmitFormAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Forms\FormDefinitionResource;
use App\Http\Resources\Forms\FormSubmissionResource;
use App\Http\Resources\Forms\FormVersionResource;
use App\Models\FormDefinition;
use App\Models\FormVersion;
use App\Models\Patient;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class FormDefinitionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->assertCan($request, 'forms.view');

        $tenantId = TenantContext::id();
        $ownerKeys = ['platform'];
        if ($tenantId !== null) {
            $ownerKeys[] = 'tenant:'.$tenantId;
        }

        $query = FormDefinition::query()
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
            $query->where('status', FormDefinition::STATUS_PUBLISHED);
        }

        return FormDefinitionResource::collection($query->paginate());
    }

    public function show(Request $request, FormDefinition $form): FormDefinitionResource
    {
        $this->assertCan($request, 'forms.view');
        $this->assertVisible($form);
        $form->load(['activeVersion', 'versions']);

        return new FormDefinitionResource($form);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertCan($request, 'forms.manage');

        $tenantId = TenantContext::id();
        abort_if($tenantId === null, 403, 'Tenant context required.');

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'type' => ['required', 'string', 'max:50'],
            'module_key' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'schema' => ['required', 'array'],
            'schema.fields' => ['required', 'array', 'min:1'],
            'label' => ['nullable', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
            'publish' => ['sometimes', 'boolean'],
        ]);

        $form = FormDefinition::query()->create([
            'tenant_id' => $tenantId,
            'owner_key' => 'tenant:'.$tenantId,
            'key' => $data['key'],
            'type' => $data['type'],
            'module_key' => $data['module_key'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        $version = FormVersion::query()->create([
            'form_definition_id' => $form->id,
            'version' => 1,
            'label' => $data['label'] ?? 'v1',
            'schema' => $data['schema'],
            'created_by_user_id' => $request->user()?->id,
        ]);

        if ($request->boolean('publish')) {
            app(PublishFormVersionAction::class)->handle($form, $version, $request->user());
            $form->refresh();
        }

        $form->load(['activeVersion', 'versions']);

        return (new FormDefinitionResource($form))
            ->response()
            ->setStatusCode(201);
    }

    public function storeVersion(Request $request, FormDefinition $form): JsonResponse
    {
        $this->assertCan($request, 'forms.manage');
        $this->assertTenantOwned($form);

        $data = $request->validate([
            'schema' => ['required', 'array'],
            'schema.fields' => ['required', 'array', 'min:1'],
            'label' => ['nullable', 'string', 'max:100'],
            'publish' => ['sometimes', 'boolean'],
        ]);

        $next = (int) $form->versions()->max('version') + 1;
        $version = FormVersion::query()->create([
            'form_definition_id' => $form->id,
            'version' => $next,
            'label' => $data['label'] ?? 'v'.$next,
            'schema' => $data['schema'],
            'created_by_user_id' => $request->user()?->id,
        ]);

        if ($request->boolean('publish')) {
            app(PublishFormVersionAction::class)->handle($form, $version, $request->user());
        }

        return (new FormVersionResource($version->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function publish(
        Request $request,
        FormDefinition $form,
        FormVersion $version,
        PublishFormVersionAction $action,
    ): FormVersionResource {
        $this->assertCan($request, 'forms.manage');
        $this->assertTenantOwned($form);
        abort_unless($version->form_definition_id === $form->id, 404);

        return new FormVersionResource($action->handle($form, $version, $request->user()));
    }

    public function submit(
        Request $request,
        FormDefinition $form,
        SubmitFormAction $action,
    ): JsonResponse {
        $this->assertCan($request, 'forms.manage');
        $this->assertVisible($form);

        $data = $request->validate([
            'payload' => ['required', 'array'],
            'meta' => ['nullable', 'array'],
            'patient_id' => ['nullable', 'integer', Rule::exists('patients', 'id')],
        ]);

        $subject = null;
        if (! empty($data['patient_id'])) {
            $subject = Patient::query()->findOrFail($data['patient_id']);
        }

        $submission = $action->handle(
            $form,
            $data['payload'],
            $request->user(),
            $subject,
            $data['meta'] ?? null,
        );

        return (new FormSubmissionResource($submission))
            ->response()
            ->setStatusCode(201);
    }

    private function assertCan(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission($permission)),
            403,
        );
    }

    private function assertVisible(FormDefinition $form): void
    {
        $tenantId = TenantContext::id();
        $allowed = $form->owner_key === 'platform'
            || ($tenantId !== null && $form->owner_key === 'tenant:'.$tenantId);
        abort_unless($allowed, 404);
    }

    private function assertTenantOwned(FormDefinition $form): void
    {
        $tenantId = TenantContext::id();
        abort_unless(
            $tenantId !== null && $form->owner_key === 'tenant:'.$tenantId,
            403,
            'Only tenant-owned forms can be mutated here.',
        );
    }
}
