<?php

namespace App\Services\AI;

use App\Contracts\AI\AgentInterface;
use App\Contracts\AI\AIProviderInterface;
use App\Models\AiAuditLog;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use App\Models\AiUsageRecord;
use App\Services\AI\DTO\AgentContext;
use App\Services\AI\DTO\AgentResult;
use App\Services\AI\DTO\GenerationResult;
use App\Services\AI\DTO\PromptRequest;
use App\Services\AI\Learning\KnowledgeRetriever;
use App\Services\AI\Learning\MemoryService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AIOrchestrator
{
    public function __construct(
        private readonly AgentRegistry $registry,
        private readonly ModelRouter $router,
        private readonly AIProviderInterface $provider,
        private readonly AiUsageQuotaService $quotaService,
        private readonly MemoryService $memoryService,
        private readonly KnowledgeRetriever $knowledgeRetriever,
    ) {}

    public function run(string $agentKey, AgentContext $context): AgentResult
    {
        $agent = $this->registry->get($agentKey);

        return $this->executeAgent($agent, $context);
    }

    public function executeAgent(AgentInterface $agent, AgentContext $context): AgentResult
    {
        $feature = $context->feature ?? $agent->defaultFeature();
        $taskType = $context->taskType ?? $agent->defaultTaskType();

        $this->quotaService->assertWithinQuota($context->tenantId);

        $routed = $this->router->route($taskType, $context->tenantId, $context->modelHint);
        $prompt = $this->resolveActivePrompt($agent->key());
        $promptVersion = $prompt?->activeVersion();

        $systemPrompt = $promptVersion?->system_prompt
            ?? "You are Health Assist {$agent->key()} assistant. Do not diagnose or prescribe.";

        $systemPrompt = $this->enrichSystemPrompt($agent->key(), $context, $systemPrompt);

        $request = $agent->buildRequest(
            $context,
            $systemPrompt,
            $routed->model,
        );

        $request = $request->mergeMetadata(array_merge(
            $routed->config,
            is_array($context->modelConfig) ? $context->modelConfig : [],
        ));

        $generation = $this->provider->generate($request);
        $requestId = (string) Str::uuid();

        $usage = $this->recordUsage($context, $agent->key(), $feature, $generation, $requestId);
        $this->quotaService->recordPackageMessageUsage($context->tenantId);
        $audit = $this->recordAudit(
            $context,
            $agent->key(),
            $feature,
            $generation,
            $prompt?->key,
            $promptVersion?->version,
            $usage?->id,
            $request,
        );

        return new AgentResult(
            content: $generation->content,
            agent: $agent->key(),
            feature: $feature,
            model: $generation->model,
            modelVersion: $generation->modelVersion,
            promptKey: $prompt?->key,
            promptVersion: $promptVersion?->version,
            usageRecordId: $usage?->id,
            auditLogId: $audit?->id,
            requestId: $requestId,
            disclaimer: (string) config('ai.disclaimer'),
            meta: [
                'task_type' => $taskType,
                'provider' => $routed->provider,
                'input_tokens' => $generation->inputTokens,
                'output_tokens' => $generation->outputTokens,
                'latency_ms' => $generation->latencyMs,
                'learning' => [
                    'weights_updated' => false,
                    'memory_injected' => filled($context->metadata['conversation_ref'] ?? null),
                    'few_shots_injected' => true,
                ],
            ],
        );
    }

    /**
     * Direct generate path for non-agent callers (tools / internal).
     */
    public function generate(PromptRequest $request, ?int $userId = null): AgentResult
    {
        $this->quotaService->assertWithinQuota($request->tenantId);

        $routed = $this->router->route($request->taskType, $request->tenantId, $request->modelHint);
        $promptRequest = $request
            ->withModelHint($routed->model)
            ->mergeMetadata($routed->config);
        $generation = $this->provider->generate($promptRequest);
        $requestId = (string) Str::uuid();

        $context = new AgentContext(
            tenantId: $request->tenantId,
            userId: $userId,
            feature: $request->feature,
        );

        $usage = $this->recordUsage($context, $request->agent, $request->feature, $generation, $requestId);
        $this->quotaService->recordPackageMessageUsage($request->tenantId);
        $audit = $this->recordAudit(
            $context,
            $request->agent,
            $request->feature,
            $generation,
            null,
            null,
            $usage?->id,
            $promptRequest,
        );

        return new AgentResult(
            content: $generation->content,
            agent: $request->agent,
            feature: $request->feature,
            model: $generation->model,
            modelVersion: $generation->modelVersion,
            promptKey: null,
            promptVersion: null,
            usageRecordId: $usage?->id,
            auditLogId: $audit?->id,
            requestId: $requestId,
            disclaimer: (string) config('ai.disclaimer'),
            meta: [
                'task_type' => $request->taskType,
                'provider' => $routed->provider,
                'input_tokens' => $generation->inputTokens,
                'output_tokens' => $generation->outputTokens,
                'latency_ms' => $generation->latencyMs,
                'learning' => ['weights_updated' => false],
            ],
        );
    }

    private function enrichSystemPrompt(string $agentKey, AgentContext $context, string $systemPrompt): string
    {
        $blocks = [$systemPrompt];

        $conversationRef = $context->metadata['conversation_ref'] ?? null;
        if (is_string($conversationRef) && $conversationRef !== '') {
            $memory = $this->memoryService->recallConversation($context->tenantId, $conversationRef, $agentKey);
            $block = $this->memoryService->formatMemoryBlock($memory);
            if ($block !== '') {
                $blocks[] = $block;
            }
        }

        $shots = $this->memoryService->approvedFewShots($context->tenantId, $agentKey, 3);
        $shotBlock = $this->memoryService->formatFewShotBlock($shots);
        if ($shotBlock !== '') {
            $blocks[] = $shotBlock;
        }

        $query = $context->input
            ?? collect($context->messages)->last()['content']
            ?? null;
        if (is_string($query) && $query !== '' && in_array($agentKey, ['patient', 'report', 'clinical', 'physiotherapy', 'health_guide'], true)) {
            $hits = $this->knowledgeRetriever->retrieve($context->tenantId, $query, 3);
            $kb = $this->knowledgeRetriever->formatContextBlock($hits);
            if ($kb !== '') {
                $blocks[] = $kb;
            }
        }

        $blocks[] = 'Hard rules: never diagnose or prescribe; never override SafetyEngine; never change model weights.';

        return implode("\n\n", $blocks);
    }

    private function resolveActivePrompt(string $agentKey): ?AiPrompt
    {
        return AiPrompt::query()
            ->where('key', $agentKey)
            ->with(['versions' => fn ($q) => $q->where('status', AiPromptVersion::STATUS_ACTIVE)])
            ->first();
    }

    private function recordUsage(
        AgentContext $context,
        string $agent,
        string $feature,
        GenerationResult $generation,
        string $requestId,
    ): ?AiUsageRecord {
        if (! config('ai.metering.enabled', true)) {
            return null;
        }

        return AiUsageRecord::query()->create([
            'tenant_id' => $context->tenantId,
            'user_id' => $context->userId,
            'agent' => $agent,
            'feature' => $feature,
            'model' => $generation->model,
            'model_version' => $generation->modelVersion,
            'input_tokens' => $generation->inputTokens,
            'output_tokens' => $generation->outputTokens,
            'latency_ms' => $generation->latencyMs,
            'status' => 'success',
            'estimated_cost_cents' => $this->quotaService->estimateCostCents(
                (int) $generation->inputTokens,
                (int) $generation->outputTokens,
                $generation->model,
            ),
            'request_id' => $requestId,
        ]);
    }

    private function recordAudit(
        AgentContext $context,
        string $agent,
        string $feature,
        GenerationResult $generation,
        ?string $promptKey,
        ?int $promptVersion,
        ?int $usageRecordId,
        PromptRequest $request,
    ): ?AiAuditLog {
        $limit = (int) config('ai.audit.store_redacted_preview_chars', 200);
        $inputPreview = $this->redactPreview($this->flattenInput($request), $limit);
        $outputPreview = $this->redactPreview($generation->content, $limit);

        return AiAuditLog::query()->create([
            'tenant_id' => $context->tenantId,
            'user_id' => $context->userId,
            'agent' => $agent,
            'model' => $generation->model,
            'model_version' => $generation->modelVersion,
            'prompt_key' => $promptKey,
            'prompt_version' => $promptVersion,
            'input_type' => 'text',
            'input_redacted' => $inputPreview,
            'output_redacted' => $outputPreview,
            'review_status' => AiAuditLog::REVIEW_NOT_REQUIRED,
            'usage_record_id' => $usageRecordId,
            'meta' => [
                'feature' => $feature,
                'input_hash' => hash('sha256', $this->flattenInput($request)),
                'output_hash' => hash('sha256', $generation->content),
                'sanitized_provider_meta' => Arr::only($generation->rawMeta, ['provider', 'deterministic', 'method', 'finish_reason']),
            ],
        ]);
    }

    private function flattenInput(PromptRequest $request): string
    {
        $parts = [];
        if (filled($request->system)) {
            $parts[] = (string) $request->system;
        }
        foreach ($request->messages as $message) {
            $parts[] = ($message['role'] ?? 'user').':'.($message['content'] ?? '');
        }

        return implode("\n", $parts);
    }

    private function redactPreview(string $text, int $limit): string
    {
        $trimmed = trim($text);
        if (mb_strlen($trimmed) <= $limit) {
            return $trimmed;
        }

        return mb_substr($trimmed, 0, $limit).'…';
    }
}
