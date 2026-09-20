<?php

namespace App\Contracts\AI;

use App\Services\AI\DTO\AgentContext;
use App\Services\AI\DTO\AgentResult;
use App\Services\AI\DTO\PromptRequest;

interface AgentInterface
{
    public function key(): string;

    public function name(): string;

    public function description(): string;

    public function defaultFeature(): string;

    public function defaultTaskType(): string;

    public function buildRequest(AgentContext $context, string $systemPrompt, ?string $modelHint = null): PromptRequest;

    public function handle(AgentContext $context): AgentResult;
}
