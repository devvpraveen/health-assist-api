<?php

namespace App\Services\AI\Learning;

use App\Models\AiTrainingDataset;
use App\Models\AiTrainingDatasetItem;
use Illuminate\Support\Facades\Storage;

class DatasetExporter
{
    /**
     * Write a de-identified JSONL export. Does not train.
     */
    public function export(AiTrainingDataset $dataset): string
    {
        $dataset->loadMissing('items');

        $relative = sprintf(
            'ai-training/%s/%s-v%s.jsonl',
            $dataset->id,
            $dataset->key,
            $dataset->version,
        );

        $lines = $dataset->items->map(function (AiTrainingDatasetItem $item): string {
            return json_encode([
                'prompt' => (string) $item->prompt_redacted,
                'completion' => (string) $item->completion_redacted,
                'agent' => $item->agent,
                'deidentified' => (bool) data_get($item->meta, 'deidentified', false),
                'candidate_id' => $item->candidate_id,
            ], JSON_UNESCAPED_UNICODE);
        })->implode("\n");

        Storage::disk($this->disk())->put($relative, $lines === '' ? '' : $lines."\n");

        $dataset->update([
            'status' => 'exported',
            'meta' => array_merge(is_array($dataset->meta) ? $dataset->meta : [], [
                'export_path' => $relative,
                'exported_at' => now()->toIso8601String(),
            ]),
        ]);

        return $relative;
    }

    public function absolutePath(string $relative): string
    {
        return Storage::disk($this->disk())->path($relative);
    }

    public function disk(): string
    {
        return (string) config('ai.training.disk', 'local');
    }
}
