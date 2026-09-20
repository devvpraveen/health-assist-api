<?php

namespace App\Services\AI\Learning;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Invokes the offline trainer. Mock driver is used in CI; python is optional.
 */
class TrainingWorkerClient
{
    /**
     * @return array<string, mixed>
     */
    public function run(string $driver, string $inputAbsolute, string $outputAbsolute): array
    {
        return match ($driver) {
            'mock' => $this->runMock($inputAbsolute, $outputAbsolute),
            'python' => $this->runPython($inputAbsolute, $outputAbsolute),
            default => throw new RuntimeException("Unknown training driver [{$driver}]."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function runMock(string $inputAbsolute, string $outputAbsolute): array
    {
        $rows = $this->readJsonl($inputAbsolute);
        if ($rows === []) {
            throw new RuntimeException('Training export is empty.');
        }

        $promptLens = array_map(fn (array $row): int => strlen((string) ($row['prompt'] ?? '')), $rows);
        $completionLens = array_map(fn (array $row): int => strlen((string) ($row['completion'] ?? '')), $rows);
        $deidentified = count(array_filter($rows, fn (array $row): bool => ($row['deidentified'] ?? false) === true));

        $avgCompletion = array_sum($completionLens) / max(1, count($completionLens));
        $completeness = min(1.0, $avgCompletion / 80.0);
        $privacy = $deidentified / count($rows);
        $groundedness = round(min(1.0, 0.4 + $completeness * 0.4 + $privacy * 0.2), 4);
        $helpfulness = round(min(1.0, 0.45 + $completeness * 0.35), 4);
        $safety = 1.0;
        $overall = round(($groundedness + $helpfulness + $safety + $privacy) / 4, 4);

        $result = [
            'ok' => true,
            'weights_updated_in_production' => false,
            'lifecycle_suggested' => 'candidate',
            'item_count' => count($rows),
            'deidentified_count' => $deidentified,
            'metrics' => [
                'overall_score' => $overall,
                'groundedness' => $groundedness,
                'helpfulness' => $helpfulness,
                'safety' => $safety,
                'privacy' => round($privacy, 4),
                'avg_prompt_chars' => round(array_sum($promptLens) / max(1, count($promptLens)), 2),
                'avg_completion_chars' => round($avgCompletion, 2),
            ],
            'artifact' => [
                'kind' => 'offline_adapter_stub',
                'driver' => 'mock',
            ],
            'finished_at' => now()->toIso8601String(),
        ];

        $this->writeResult($outputAbsolute, $result);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function runPython(string $inputAbsolute, string $outputAbsolute): array
    {
        $script = (string) config('ai.training.python_script');
        $binary = (string) config('ai.training.python_binary', 'python3');

        if (! is_file($script)) {
            throw new RuntimeException("Python training worker not found at [{$script}].");
        }

        $process = Process::timeout((int) config('ai.training.timeout_seconds', 120))
            ->run([
                $binary,
                $script,
                '--input',
                $inputAbsolute,
                '--output',
                $outputAbsolute,
            ]);

        if (! $process->successful()) {
            throw new RuntimeException(trim($process->errorOutput() ?: $process->output()) ?: 'Python training worker failed.');
        }

        if (! is_file($outputAbsolute)) {
            throw new RuntimeException('Python training worker did not write a result file.');
        }

        $decoded = json_decode((string) file_get_contents($outputAbsolute), true);
        if (! is_array($decoded) || ($decoded['ok'] ?? false) !== true) {
            throw new RuntimeException((string) ($decoded['error'] ?? 'Python training worker reported failure.'));
        }

        if (($decoded['weights_updated_in_production'] ?? null) === true) {
            throw new RuntimeException('Worker must not report production weight updates.');
        }

        return $decoded;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readJsonl(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Training export not found [{$path}].");
        }

        $rows = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function writeResult(string $path, array $result): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
