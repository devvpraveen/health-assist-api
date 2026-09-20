<?php

namespace App\Services\AI\DTO;

readonly class PromptRequest
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $tenantId,
        public string $agent,
        public string $feature,
        public string $taskType,
        public array $messages,
        public ?string $system = null,
        public array $metadata = [],
        public ?string $modelHint = null,
    ) {}

    public function withSystem(?string $system): self
    {
        return new self(
            tenantId: $this->tenantId,
            agent: $this->agent,
            feature: $this->feature,
            taskType: $this->taskType,
            messages: $this->messages,
            system: $system,
            metadata: $this->metadata,
            modelHint: $this->modelHint,
        );
    }

    public function withModelHint(?string $modelHint): self
    {
        return new self(
            tenantId: $this->tenantId,
            agent: $this->agent,
            feature: $this->feature,
            taskType: $this->taskType,
            messages: $this->messages,
            system: $this->system,
            metadata: $this->metadata,
            modelHint: $modelHint,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            tenantId: $this->tenantId,
            agent: $this->agent,
            feature: $this->feature,
            taskType: $this->taskType,
            messages: $this->messages,
            system: $this->system,
            metadata: $metadata,
            modelHint: $this->modelHint,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function mergeMetadata(array $metadata): self
    {
        return $this->withMetadata(array_merge($this->metadata, $metadata));
    }
}
