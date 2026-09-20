<?php

namespace App\Services\AI\Learning;

use App\Models\AiConversationMemory;
use App\Models\AiLearningCandidate;
use App\Models\AiWorkflowMemory;
use Illuminate\Support\Arr;

class MemoryService
{
    /**
     * @param  array<string, mixed>  $structured
     */
    public function rememberConversation(
        int $tenantId,
        string $conversationRef,
        string $agent,
        array $structured,
        ?int $patientId = null,
        ?int $userId = null,
        ?int $ttlHours = 72,
    ): AiConversationMemory {
        $existing = AiConversationMemory::query()
            ->where('tenant_id', $tenantId)
            ->where('conversation_ref', $conversationRef)
            ->where('agent', $agent)
            ->first();

        $merged = array_merge(
            is_array($existing?->structured_json) ? $existing->structured_json : [],
            Arr::where($structured, fn ($v) => $v !== null && $v !== ''),
        );

        return AiConversationMemory::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'conversation_ref' => $conversationRef,
                'agent' => $agent,
            ],
            [
                'patient_id' => $patientId,
                'user_id' => $userId,
                'structured_json' => $merged,
                'expires_at' => $ttlHours ? now()->addHours($ttlHours) : null,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function recallConversation(int $tenantId, string $conversationRef, string $agent): array
    {
        $row = AiConversationMemory::query()
            ->where('tenant_id', $tenantId)
            ->where('conversation_ref', $conversationRef)
            ->where('agent', $agent)
            ->where(function ($q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        return is_array($row?->structured_json) ? $row->structured_json : [];
    }

    public function formatMemoryBlock(array $structured): string
    {
        if ($structured === []) {
            return '';
        }

        $lines = ['Known session facts (memory — do not invent beyond these):'];
        foreach ($structured as $key => $value) {
            if (is_scalar($value)) {
                $lines[] = '- '.$key.': '.$value;
            } elseif (is_array($value)) {
                $lines[] = '- '.$key.': '.json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $toolChain
     */
    public function recordWorkflowOutcome(
        int $tenantId,
        string $agent,
        string $intent,
        array $toolChain,
        bool $success,
        float $delta = 1.0,
    ): AiWorkflowMemory {
        $row = AiWorkflowMemory::query()->firstOrNew([
            'tenant_id' => $tenantId,
            'agent' => $agent,
            'intent' => $intent,
        ]);

        $row->tool_chain = $toolChain;
        if ($success) {
            $row->success_count = (int) $row->success_count + 1;
            $row->outcome_score = min(100, (float) $row->outcome_score + $delta);
        } else {
            $row->failure_count = (int) $row->failure_count + 1;
            $row->outcome_score = max(0, (float) $row->outcome_score - $delta);
        }
        $row->save();

        return $row;
    }

    /**
     * Approved few-shot corrections for prompt injection (not weight updates).
     *
     * @return list<array{input: string, output: string}>
     */
    public function approvedFewShots(int $tenantId, string $agent, int $limit = 3): array
    {
        return AiLearningCandidate::query()
            ->where('tenant_id', $tenantId)
            ->where('agent', $agent)
            ->where('status', AiLearningCandidate::STATUS_APPROVED)
            ->whereNotNull('corrected_output')
            ->orderByDesc('reviewed_at')
            ->limit($limit)
            ->get()
            ->map(fn (AiLearningCandidate $c) => [
                'input' => (string) ($c->input_redacted ?? ''),
                'output' => (string) $c->corrected_output,
            ])
            ->all();
    }

    public function formatFewShotBlock(array $shots): string
    {
        if ($shots === []) {
            return '';
        }

        $parts = ['Approved correction examples (follow style; do not copy PHI):'];
        foreach ($shots as $i => $shot) {
            $n = $i + 1;
            $parts[] = "Example {$n} input: ".mb_substr((string) ($shot['input'] ?? ''), 0, 400);
            $parts[] = "Example {$n} preferred output: ".mb_substr((string) ($shot['output'] ?? ''), 0, 800);
        }

        return implode("\n", $parts);
    }
}
