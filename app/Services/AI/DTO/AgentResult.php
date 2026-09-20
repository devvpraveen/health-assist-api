<?php

namespace App\Services\AI\DTO;

readonly class AgentResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $content,
        public string $agent,
        public string $feature,
        public string $model,
        public string $modelVersion,
        public ?string $promptKey,
        public ?int $promptVersion,
        public ?int $usageRecordId,
        public ?int $auditLogId,
        public string $requestId,
        public string $disclaimer,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'agent' => $this->agent,
            'feature' => $this->feature,
            'model' => $this->model,
            'model_version' => $this->modelVersion,
            'prompt_key' => $this->promptKey,
            'prompt_version' => $this->promptVersion,
            'usage_record_id' => $this->usageRecordId,
            'audit_log_id' => $this->auditLogId,
            'request_id' => $this->requestId,
            'disclaimer' => $this->disclaimer,
            'meta' => $this->meta,
        ];
    }
}
