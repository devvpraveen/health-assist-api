<?php

namespace App\Services\AI\DTO;

readonly class RoutedModel
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $provider,
        public string $model,
        public string $modelVersion,
        public string $taskType,
        public array $config = [],
    ) {}
}
