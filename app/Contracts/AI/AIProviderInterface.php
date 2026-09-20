<?php

namespace App\Contracts\AI;

use App\Services\AI\DTO\GenerationResult;
use App\Services\AI\DTO\PromptRequest;
use Generator;

interface AIProviderInterface
{
    public function generate(PromptRequest $request): GenerationResult;

    /**
     * Optional streaming. Mock yields chunks; providers may throw if unsupported.
     *
     * @return Generator<int, string>
     */
    public function stream(PromptRequest $request): Generator;

    public function classify(PromptRequest $request): GenerationResult;

    /**
     * @return list<float>
     */
    public function embed(PromptRequest $request): array;

    public function transcribe(PromptRequest $request): GenerationResult;

    public function analyzeImage(PromptRequest $request): GenerationResult;

    public function analyzeDocument(PromptRequest $request): GenerationResult;
}
