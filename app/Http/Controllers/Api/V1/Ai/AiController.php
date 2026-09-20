<?php

namespace App\Http\Controllers\Api\V1\Ai;

use App\Exceptions\AI\AiQuotaExceededException;
use App\Actions\AI\ActivatePromptVersionAction;
use App\Actions\AI\CreatePromptVersionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ai\RunAiAgentRequest;
use App\Http\Requests\Api\V1\Ai\StoreAiPromptVersionRequest;
use App\Http\Resources\Ai\AiAuditLogResource;
use App\Http\Resources\Ai\AiModelResource;
use App\Http\Resources\Ai\AiPromptResource;
use App\Http\Resources\Ai\AiPromptVersionResource;
use App\Http\Resources\Ai\AiUsageRecordResource;
use App\Models\AiAuditLog;
use App\Models\AiModel;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use App\Models\AiUsageRecord;
use App\Models\User;
use App\Services\AI\AgentRegistry;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\AiUsageQuotaService;
use App\Services\AI\DTO\AgentContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AiController extends Controller
{
    public function agents(Request $request, AgentRegistry $registry): JsonResponse
    {
        $this->ensureCanView($request->user());

        return response()->json([
            'data' => $registry->list(),
            'disclaimer' => config('ai.disclaimer'),
        ]);
    }

    public function run(
        RunAiAgentRequest $request,
        string $agent,
        AIOrchestrator $orchestrator,
        AgentRegistry $registry,
    ): JsonResponse {
        if (! $registry->has($agent)) {
            throw new NotFoundHttpException("Unknown AI agent [{$agent}].");
        }

        $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
        if ($tenantId === null) {
            abort(403, 'Tenant context required.');
        }

        $validated = $request->validated();

        try {
            $result = $orchestrator->run($agent, new AgentContext(
                tenantId: (int) $tenantId,
                userId: $request->user()?->id,
                feature: $validated['feature'] ?? null,
                input: $validated['input'] ?? null,
                messages: $validated['messages'] ?? [],
                metadata: $validated['metadata'] ?? [],
                taskType: $validated['task_type'] ?? null,
                modelHint: $validated['model'] ?? null,
                modelConfig: $validated['model_config'] ?? null,
            ));
        } catch (AiQuotaExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'quota' => $e->snapshot,
                'disclaimer' => config('ai.disclaimer'),
            ], 429);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        return response()->json([
            'data' => $result->toArray(),
            'disclaimer' => $result->disclaimer,
        ]);
    }

    public function usageSummary(Request $request, AiUsageQuotaService $quotaService): JsonResponse
    {
        $this->ensureCanView($request->user());

        return response()->json([
            'data' => $quotaService->summary(TenantContext::id() ?? $request->user()?->tenant_id),
            'disclaimer' => config('ai.disclaimer'),
        ]);
    }

    public function usage(Request $request): AnonymousResourceCollection
    {
        $this->ensureCanView($request->user());

        $records = AiUsageRecord::query()
            ->orderByDesc('created_at')
            ->paginate();

        return AiUsageRecordResource::collection($records);
    }

    public function auditLogs(Request $request): AnonymousResourceCollection
    {
        $this->ensureCanView($request->user());

        $logs = AiAuditLog::query()
            ->orderByDesc('created_at')
            ->paginate();

        return AiAuditLogResource::collection($logs);
    }

    public function prompts(Request $request): AnonymousResourceCollection
    {
        $this->ensureCanView($request->user());

        $prompts = AiPrompt::query()
            ->with('activeVersionRelation')
            ->orderBy('key')
            ->paginate();

        return AiPromptResource::collection($prompts);
    }

    public function promptVersions(Request $request, AiPrompt $prompt): AnonymousResourceCollection
    {
        $this->ensureCanView($request->user());

        $versions = $prompt->versions()
            ->orderByDesc('version')
            ->paginate();

        return AiPromptVersionResource::collection($versions);
    }

    public function storePromptVersion(
        StoreAiPromptVersionRequest $request,
        AiPrompt $prompt,
        CreatePromptVersionAction $action,
    ): JsonResponse {
        $version = $action->handle($prompt, $request->validated(), $request->user()?->id);

        return (new AiPromptVersionResource($version))
            ->response()
            ->setStatusCode(201);
    }

    public function activatePromptVersion(
        Request $request,
        AiPromptVersion $version,
        ActivatePromptVersionAction $action,
    ): AiPromptVersionResource {
        $this->ensureCanManage($request->user());

        return new AiPromptVersionResource($action->handle($version));
    }

    public function models(Request $request): AnonymousResourceCollection
    {
        $this->ensureCanView($request->user());

        $includeInactive = $request->boolean('include_inactive')
            && ($request->user()?->isSuperAdmin() || $request->user()?->hasPermission('ai.manage'));

        $models = AiModel::query()
            ->with(['provider', 'versions'])
            ->when(! $includeInactive, fn ($q) => $q->where('is_active', true))
            ->orderBy('key')
            ->paginate();

        return AiModelResource::collection($models);
    }

    private function ensureCanView(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('ai.view')),
            403,
        );
    }

    private function ensureCanManage(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->isSuperAdmin() || $user->hasPermission('ai.manage')),
            403,
        );
    }
}
