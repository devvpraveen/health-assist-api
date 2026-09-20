<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\AIProviderInterface;
use App\Services\AI\DTO\GenerationResult;
use App\Services\AI\DTO\PromptRequest;
use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAICompatibleProvider implements AIProviderInterface
{
    public function generate(PromptRequest $request): GenerationResult
    {
        $apiKey = config('ai.providers.openai_compatible.api_key');
        $baseUrl = rtrim((string) (
            $request->metadata['base_url_override']
            ?? config('ai.providers.openai_compatible.base_url')
        ), '/');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI-compatible provider is not configured.');
        }

        $model = $request->modelHint
            ?? (string) config('ai.providers.openai_compatible.default_model', 'gpt-4o-mini');

        $messages = [];
        if (filled($request->system)) {
            $messages[] = ['role' => 'system', 'content' => $request->system];
        }

        foreach ($request->messages as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $body = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) ($request->metadata['temperature']
                ?? config('ai.generation_defaults.temperature', 0.2)),
        ];

        if (isset($request->metadata['max_tokens'])) {
            $body['max_tokens'] = (int) $request->metadata['max_tokens'];
        } elseif (config('ai.generation_defaults.max_tokens') !== null) {
            $body['max_tokens'] = (int) config('ai.generation_defaults.max_tokens');
        }

        if (isset($request->metadata['top_p'])) {
            $body['top_p'] = (float) $request->metadata['top_p'];
        }

        $timeout = (int) ($request->metadata['timeout']
            ?? config('ai.providers.openai_compatible.timeout', 30));

        $started = hrtime(true);

        try {
            $http = Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->acceptJson()
                ->timeout($timeout);

            if (is_array($request->metadata['headers'] ?? null)) {
                $http = $http->withHeaders($request->metadata['headers']);
            }

            $response = $http->post('/chat/completions', $body);
        } catch (ConnectionException $e) {
            throw new RuntimeException('OpenAI-compatible provider request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI-compatible provider error: HTTP '.$response->status());
        }

        $payload = $response->json();
        $content = (string) data_get($payload, 'choices.0.message.content', '');
        $inputTokens = (int) data_get($payload, 'usage.prompt_tokens', 0);
        $outputTokens = (int) data_get($payload, 'usage.completion_tokens', 0);
        $latencyMs = max(1, (int) ((hrtime(true) - $started) / 1_000_000));

        return new GenerationResult(
            content: $content,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            model: (string) data_get($payload, 'model', $model),
            modelVersion: (string) data_get($payload, 'model', $model),
            latencyMs: $latencyMs,
            rawMeta: [
                'provider' => 'openai_compatible',
                'id' => data_get($payload, 'id'),
                'finish_reason' => data_get($payload, 'choices.0.finish_reason'),
            ],
        );
    }

    public function stream(PromptRequest $request): Generator
    {
        throw new RuntimeException('OpenAI-compatible streaming is not configured.');
    }

    public function classify(PromptRequest $request): GenerationResult
    {
        throw new RuntimeException('OpenAI-compatible classify is not configured.');
    }

    public function embed(PromptRequest $request): array
    {
        throw new RuntimeException('OpenAI-compatible embed is not configured.');
    }

    public function transcribe(PromptRequest $request): GenerationResult
    {
        throw new RuntimeException('OpenAI-compatible transcribe is not configured.');
    }

    public function analyzeImage(PromptRequest $request): GenerationResult
    {
        throw new RuntimeException('OpenAI-compatible analyzeImage is not configured.');
    }

    public function analyzeDocument(PromptRequest $request): GenerationResult
    {
        throw new RuntimeException('OpenAI-compatible analyzeDocument is not configured.');
    }
}
