<?php

namespace App\Services\AI\Agents;

use App\Contracts\AI\AgentInterface;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\AgentContext;
use App\Services\AI\DTO\AgentResult;
use App\Services\AI\DTO\PromptRequest;
use App\Services\AI\LayerCCatalog;

abstract class AbstractAgent implements AgentInterface
{
    abstract public function key(): string;

    public function name(): string
    {
        return str($this->key())->replace('_', ' ')->title()->toString();
    }

    public function description(): string
    {
        return 'Health Assist Layer C agent for '.$this->key().'. Assistive only — never diagnoses or prescribe.';
    }

    public function defaultFeature(): string
    {
        return $this->key().'.assist';
    }

    public function defaultTaskType(): string
    {
        return 'general_conversation';
    }

    public function layer(): string
    {
        return LayerCCatalog::LAYERS[$this->key()] ?? 'business';
    }

    public function maturity(): string
    {
        return LayerCCatalog::MATURITY[$this->key()] ?? 'stub';
    }

    /**
     * @return list<string>
     */
    public function tools(): array
    {
        return [];
    }

    public function buildRequest(AgentContext $context, string $systemPrompt, ?string $modelHint = null): PromptRequest
    {
        $messages = $context->messages;

        if ($messages === [] && filled($context->input)) {
            $messages = [
                ['role' => 'user', 'content' => (string) $context->input],
            ];
        }

        if ($messages === []) {
            $messages = [
                ['role' => 'user', 'content' => 'Ping'],
            ];
        }

        return new PromptRequest(
            tenantId: $context->tenantId,
            agent: $this->key(),
            feature: $context->feature ?? $this->defaultFeature(),
            taskType: $context->taskType ?? $this->defaultTaskType(),
            messages: $messages,
            system: $systemPrompt,
            metadata: $context->metadata,
            modelHint: $modelHint,
        );
    }

    public function handle(AgentContext $context): AgentResult
    {
        return app(AIOrchestrator::class)->executeAgent($this, $context);
    }
}
