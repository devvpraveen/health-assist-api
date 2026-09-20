<?php

namespace App\Services\AI\DTO;

readonly class AgentContext
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $modelConfig
     */
    public function __construct(
        public int $tenantId,
        public ?int $userId = null,
        public ?string $feature = null,
        public ?string $input = null,
        public array $messages = [],
        public array $metadata = [],
        public ?string $taskType = null,
        public ?string $modelHint = null,
        public ?array $modelConfig = null,
    ) {}
}
