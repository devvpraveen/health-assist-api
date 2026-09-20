<?php

namespace Tests\Feature;

use App\Contracts\AI\AIProviderInterface;
use App\Services\AI\DTO\PromptRequest;
use App\Services\AI\Providers\OpenAICompatibleProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAICompatibleProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_uses_http_fake_success_path(): void
    {
        config([
            'ai.default_provider' => 'openai_compatible',
            'ai.providers.openai_compatible.api_key' => 'test-key',
            'ai.providers.openai_compatible.base_url' => 'https://api.example.test/v1',
            'ai.providers.openai_compatible.default_model' => 'gpt-4o-mini',
        ]);

        $this->app->forgetInstance(AIProviderInterface::class);
        $this->app->instance(AIProviderInterface::class, $this->app->make(OpenAICompatibleProvider::class));

        Http::fake([
            'api.example.test/*' => Http::response([
                'id' => 'chatcmpl-test',
                'model' => 'gpt-4o-mini',
                'choices' => [
                    [
                        'message' => ['role' => 'assistant', 'content' => 'Hello from fake OpenAI'],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 11,
                    'completion_tokens' => 7,
                ],
            ], 200),
        ]);

        $result = app(OpenAICompatibleProvider::class)->generate(new PromptRequest(
            tenantId: 1,
            agent: 'patient',
            feature: 'patient.assist',
            taskType: 'general_conversation',
            messages: [['role' => 'user', 'content' => 'Hi']],
            system: 'You are Health Assist patient assistant. Do not diagnose or prescribe.',
        ));

        $this->assertSame('Hello from fake OpenAI', $result->content);
        $this->assertSame(11, $result->inputTokens);
        $this->assertSame(7, $result->outputTokens);
        $this->assertSame('gpt-4o-mini', $result->model);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/chat/completions')
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_unsupported_methods_throw_not_configured(): void
    {
        $provider = app(OpenAICompatibleProvider::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not configured');

        $provider->embed(new PromptRequest(
            tenantId: 1,
            agent: 'analytics',
            feature: 'analytics.assist',
            taskType: 'embeddings',
            messages: [['role' => 'user', 'content' => 'embed me']],
        ));
    }
}
