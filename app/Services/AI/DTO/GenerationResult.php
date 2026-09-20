<?php

namespace App\Services\AI\DTO;

readonly class GenerationResult
{
    /**
     * @param  array<string, mixed>  $rawMeta
     */
    public function __construct(
        public string $content,
        public int $inputTokens,
        public int $outputTokens,
        public string $model,
        public string $modelVersion,
        public int $latencyMs,
        public array $rawMeta = [],
    ) {}
}
