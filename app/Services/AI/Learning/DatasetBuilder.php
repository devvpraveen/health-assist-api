<?php

namespace App\Services\AI\Learning;

use App\Models\AiLearningCandidate;
use App\Models\AiTrainingDataset;
use App\Models\AiTrainingDatasetItem;
use Illuminate\Support\Str;

class DatasetBuilder
{
    /**
     * Build a versioned dataset from approved, preferably de-identified candidates.
     * Does not fine-tune; export/training is a separate gated job.
     */
    public function buildFromApproved(
        string $key,
        string $name,
        ?string $agent = null,
        ?int $tenantId = null,
        int $limit = 500,
    ): AiTrainingDataset {
        $version = now()->format('YmdHis');

        $dataset = AiTrainingDataset::query()->create([
            'tenant_id' => $tenantId,
            'key' => $key,
            'name' => $name,
            'version' => $version,
            'agent' => $agent,
            'status' => 'draft',
            'item_count' => 0,
            'meta' => ['builder' => 'DatasetBuilder'],
        ]);

        $query = AiLearningCandidate::query()
            ->where('status', AiLearningCandidate::STATUS_APPROVED)
            ->whereNotNull('corrected_output')
            ->when($agent, fn ($q) => $q->where('agent', $agent))
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->orderByDesc('reviewed_at')
            ->limit($limit);

        $count = 0;
        foreach ($query->cursor() as $candidate) {
            /** @var AiLearningCandidate $candidate */
            AiTrainingDatasetItem::query()->create([
                'dataset_id' => $dataset->id,
                'candidate_id' => $candidate->id,
                'agent' => $candidate->agent,
                'prompt_redacted' => $this->redact((string) $candidate->input_redacted),
                'completion_redacted' => $this->redact((string) $candidate->corrected_output),
                'meta' => ['deidentified' => $candidate->deidentified],
            ]);

            $candidate->update(['status' => AiLearningCandidate::STATUS_QUEUED]);
            $count++;
        }

        $dataset->update([
            'item_count' => $count,
            'status' => $count > 0 ? 'ready' : 'draft',
        ]);

        return $dataset->refresh();
    }

    private function redact(string $text): string
    {
        $trimmed = trim($text);
        // Lightweight phone/email scrub — training export must still pass human review.
        $trimmed = preg_replace('/\b[\w.+-]+@[\w.-]+\.\w+\b/', '[email]', $trimmed) ?? $trimmed;
        $trimmed = preg_replace('/\b\+?\d[\d\s\-()]{7,}\b/', '[phone]', $trimmed) ?? $trimmed;

        return Str::limit($trimmed, 8000, '');
    }
}
