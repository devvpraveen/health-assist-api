<?php

namespace App\Services\AI\Providers;

use App\Contracts\AI\AIProviderInterface;
use App\Services\AI\DTO\GenerationResult;
use App\Services\AI\DTO\PromptRequest;
use Generator;

class MockAIProvider implements AIProviderInterface
{
    public function generate(PromptRequest $request): GenerationResult
    {
        $started = hrtime(true);
        $userText = $this->lastUserContent($request);

        if ($request->agent === 'report'
            || str_starts_with((string) $request->feature, 'report.')) {
            $content = $this->reportPayload($request, $userText);
        } else {
            $patientText = $this->patientFacingUserText($userText);
            $quoted = mb_substr($patientText, 0, 160);
            $content = 'Based on the information you shared, I can help you think through a calm next step. This is not a diagnosis.'
                ."\n\nYou said: \"{$quoted}\"\n\nIf this may be an emergency, contact local emergency services. Otherwise, a healthcare professional should interpret this in the context of your health.";
        }

        $inputTokens = max(1, (int) ceil(strlen($userText) / 4));
        $outputTokens = max(1, (int) ceil(strlen($content) / 4));
        $latencyMs = max(1, (int) ((hrtime(true) - $started) / 1_000_000));

        return new GenerationResult(
            content: $content,
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            model: $request->modelHint ?? 'mock-chat',
            modelVersion: '1',
            latencyMs: $latencyMs,
            rawMeta: [
                'provider' => 'mock',
                'deterministic' => true,
            ],
        );
    }

    public function stream(PromptRequest $request): Generator
    {
        $result = $this->generate($request);
        $chunks = str_split($result->content, 40);

        foreach ($chunks as $chunk) {
            yield $chunk;
        }
    }

    public function classify(PromptRequest $request): GenerationResult
    {
        $started = hrtime(true);
        $label = 'general';
        $content = json_encode([
            'label' => $label,
            'confidence' => 0.91,
        ], JSON_THROW_ON_ERROR);

        return new GenerationResult(
            content: $content,
            inputTokens: 8,
            outputTokens: 6,
            model: $request->modelHint ?? 'mock-classify',
            modelVersion: '1',
            latencyMs: max(1, (int) ((hrtime(true) - $started) / 1_000_000)),
            rawMeta: ['provider' => 'mock', 'method' => 'classify'],
        );
    }

    public function embed(PromptRequest $request): array
    {
        $seed = crc32($this->lastUserContent($request).'|'.$request->agent);

        return [
            (($seed % 1000) / 1000),
            ((($seed >> 3) % 1000) / 1000),
            ((($seed >> 6) % 1000) / 1000),
            ((($seed >> 9) % 1000) / 1000),
        ];
    }

    public function transcribe(PromptRequest $request): GenerationResult
    {
        return new GenerationResult(
            content: 'Mock transcription of audio input.',
            inputTokens: 0,
            outputTokens: 6,
            model: $request->modelHint ?? 'mock-chat',
            modelVersion: '1',
            latencyMs: 1,
            rawMeta: ['provider' => 'mock', 'method' => 'transcribe'],
        );
    }

    public function analyzeImage(PromptRequest $request): GenerationResult
    {
        return new GenerationResult(
            content: json_encode(['summary' => 'Mock image analysis stub', 'findings' => []], JSON_THROW_ON_ERROR),
            inputTokens: 12,
            outputTokens: 10,
            model: $request->modelHint ?? 'mock-chat',
            modelVersion: '1',
            latencyMs: 1,
            rawMeta: ['provider' => 'mock', 'method' => 'analyzeImage'],
        );
    }

    public function analyzeDocument(PromptRequest $request): GenerationResult
    {
        $userText = $this->lastUserContent($request);

        return new GenerationResult(
            content: $this->reportPayload($request, $userText),
            inputTokens: 16,
            outputTokens: 48,
            model: $request->modelHint ?? 'mock-chat',
            modelVersion: '1',
            latencyMs: 1,
            rawMeta: ['provider' => 'mock', 'method' => 'analyzeDocument'],
        );
    }

    private function lastUserContent(PromptRequest $request): string
    {
        foreach (array_reverse($request->messages) as $message) {
            if (($message['role'] ?? '') === 'user') {
                return (string) ($message['content'] ?? '');
            }
        }

        return $request->system ?? '';
    }

    private function patientFacingUserText(string $raw): string
    {
        if (preg_match('/User message \(assist only[^)]*\):\s*(.+)/s', $raw, $matches) === 1) {
            $line = trim((string) explode("\n", (string) $matches[1])[0]);
            if ($line !== '') {
                return $line;
            }
        }

        return mb_substr($raw, 0, 160);
    }

    private function reportPayload(PromptRequest $request, string $userText): string
    {
        $feature = (string) ($request->feature ?? 'report.interpretation');

        $payload = [
            'agent' => 'report',
            'feature' => $feature,
            'extracted_facts' => [
                'tests' => [
                    ['name' => 'hb', 'value' => 11.2, 'unit' => 'g/dL', 'flag' => 'low'],
                    ['name' => 'wbc', 'value' => 12.5, 'unit' => 'x10^3/uL', 'flag' => 'high'],
                    ['name' => 'glucose', 'value' => 110, 'unit' => 'mg/dL', 'flag' => 'high'],
                ],
            ],
            'possible_interpretation' => 'Mock assistive interpretation: some values are outside typical reference ranges and need clinician correlation with history.',
            'uncertainty' => 'Reference ranges are heuristic only; demographics, fasting status, and assay methods are unknown.',
            'items_requiring_review' => [
                'Confirm analyte flags against lab-specific ranges',
                'Correlate abnormal values with clinical presentation',
                'Do not share with patient until clinician approval',
            ],
            'requires_clinician_review' => true,
            'patient_explanation' => 'Some of your lab values look different from common reference ranges. This is not a diagnosis. A clinician will review the results with you before any decisions are made.',
            'disclaimer' => 'assistive_only',
            'echo' => mb_substr($userText, 0, 80),
        ];

        if ($feature === 'report.patient_explanation') {
            $payload['reply'] = $payload['patient_explanation'];
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
