<?php

namespace App\Http\Controllers\Api\V1\Automation;

use App\Actions\Automation\PublishAutomationWorkflowAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Automation\AutomationWorkflowResource;
use App\Http\Resources\Automation\AutomationWorkflowRunResource;
use App\Http\Resources\Automation\AutomationWorkflowVersionResource;
use App\Models\AutomationWorkflow;
use App\Models\AutomationWorkflowRun;
use App\Models\AutomationWorkflowVersion;
use App\Services\Automation\WorkflowEngine;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AutomationWorkflowController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->assertCan($request, 'workflows.view');

        $tenantId = TenantContext::id();
        $ownerKeys = ['platform'];
        if ($tenantId !== null) {
            $ownerKeys[] = 'tenant:'.$tenantId;
        }

        $query = AutomationWorkflow::query()
            ->with('activeVersion')
            ->whereIn('owner_key', $ownerKeys)
            ->orderBy('trigger')
            ->orderBy('key');

        if ($request->filled('trigger')) {
            $query->where('trigger', $request->string('trigger')->toString());
        }

        return AutomationWorkflowResource::collection($query->paginate());
    }

    public function show(Request $request, AutomationWorkflow $workflow): AutomationWorkflowResource
    {
        $this->assertCan($request, 'workflows.view');
        $this->assertVisible($workflow);
        $workflow->load(['activeVersion', 'versions']);

        return new AutomationWorkflowResource($workflow);
    }

    public function store(Request $request, WorkflowEngine $engine): JsonResponse
    {
        $this->assertCan($request, 'workflows.manage');
        $tenantId = TenantContext::id();
        abort_if($tenantId === null, 403, 'Tenant context required.');

        $data = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger' => ['required', 'string', 'max:100'],
            'module_key' => ['nullable', 'string', 'max:50'],
            'steps' => ['required', 'array', 'min:1'],
            'label' => ['nullable', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
            'publish' => ['sometimes', 'boolean'],
        ]);

        try {
            $engine->assertStepsSafe($data['steps']);
        } catch (\InvalidArgumentException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'steps' => [$e->getMessage()],
            ]);
        }

        $workflow = AutomationWorkflow::query()->create([
            'tenant_id' => $tenantId,
            'owner_key' => 'tenant:'.$tenantId,
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger' => $data['trigger'],
            'module_key' => $data['module_key'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);

        $version = AutomationWorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version' => 1,
            'label' => $data['label'] ?? 'v1',
            'steps' => $data['steps'],
            'created_by_user_id' => $request->user()?->id,
        ]);

        if ($request->boolean('publish')) {
            app(PublishAutomationWorkflowAction::class)->handle($workflow, $version, $request->user());
            $workflow->refresh();
        }

        $workflow->load(['activeVersion', 'versions']);

        return (new AutomationWorkflowResource($workflow))
            ->response()
            ->setStatusCode(201);
    }

    public function storeVersion(Request $request, AutomationWorkflow $workflow, WorkflowEngine $engine): JsonResponse
    {
        $this->assertCan($request, 'workflows.manage');
        $this->assertTenantOwned($workflow);

        $data = $request->validate([
            'steps' => ['required', 'array', 'min:1'],
            'label' => ['nullable', 'string', 'max:100'],
            'publish' => ['sometimes', 'boolean'],
        ]);

        try {
            $engine->assertStepsSafe($data['steps']);
        } catch (\InvalidArgumentException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'steps' => [$e->getMessage()],
            ]);
        }

        $next = (int) $workflow->versions()->max('version') + 1;
        $version = AutomationWorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version' => $next,
            'label' => $data['label'] ?? 'v'.$next,
            'steps' => $data['steps'],
            'created_by_user_id' => $request->user()?->id,
        ]);

        if ($request->boolean('publish')) {
            app(PublishAutomationWorkflowAction::class)->handle($workflow, $version, $request->user());
        }

        return (new AutomationWorkflowVersionResource($version->fresh()))
            ->response()
            ->setStatusCode(201);
    }

    public function publish(
        Request $request,
        AutomationWorkflow $workflow,
        AutomationWorkflowVersion $workflowVersion,
        PublishAutomationWorkflowAction $action,
    ): AutomationWorkflowVersionResource {
        $this->assertCan($request, 'workflows.manage');
        $this->assertTenantOwned($workflow);
        abort_unless($workflowVersion->workflow_id === $workflow->id, 404);

        return new AutomationWorkflowVersionResource($action->handle($workflow, $workflowVersion, $request->user()));
    }

    public function trigger(Request $request, AutomationWorkflow $workflow, WorkflowEngine $engine): JsonResponse
    {
        $this->assertCan($request, 'workflows.manage');
        $this->assertVisible($workflow);

        $tenantId = TenantContext::id();
        abort_if($tenantId === null, 403);

        $data = $request->validate([
            'context' => ['nullable', 'array'],
        ]);

        abort_unless(
            $workflow->is_active && $workflow->status === AutomationWorkflow::STATUS_PUBLISHED && $workflow->active_version_id,
            422,
            'Workflow must be published and active.',
        );

        $run = $engine->startRun($workflow, (int) $tenantId, $workflow->trigger, $data['context'] ?? []);
        $run->load('steps');

        return (new AutomationWorkflowRunResource($run->fresh('steps')))
            ->response()
            ->setStatusCode(202);
    }

    public function runs(Request $request): AnonymousResourceCollection
    {
        $this->assertCan($request, 'workflows.view');

        $query = AutomationWorkflowRun::query()->with('steps')->latest();

        if ($request->filled('workflow_id')) {
            $query->where('workflow_id', $request->integer('workflow_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return AutomationWorkflowRunResource::collection($query->paginate());
    }

    public function showRun(Request $request, AutomationWorkflowRun $workflowRun): AutomationWorkflowRunResource
    {
        $this->assertCan($request, 'workflows.view');
        $workflowRun->load('steps');

        return new AutomationWorkflowRunResource($workflowRun);
    }

    private function assertCan(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission($permission)),
            403,
        );
    }

    private function assertVisible(AutomationWorkflow $workflow): void
    {
        $tenantId = TenantContext::id();
        $allowed = $workflow->owner_key === 'platform'
            || ($tenantId !== null && $workflow->owner_key === 'tenant:'.$tenantId);
        abort_unless($allowed, 404);
    }

    private function assertTenantOwned(AutomationWorkflow $workflow): void
    {
        $tenantId = TenantContext::id();
        abort_unless(
            $tenantId !== null && $workflow->owner_key === 'tenant:'.$tenantId,
            403,
            'Only tenant-owned workflows can be mutated here.',
        );
    }
}
