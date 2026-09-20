<?php

namespace App\Services\AI\Learning;

use App\Jobs\AI\RunTrainingJob;
use App\Models\AiEvalRun;
use App\Models\AiModel;
use App\Models\AiModelVersion;
use App\Models\AiProvider;
use App\Models\AiTrainingDataset;
use App\Models\AiTrainingJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gated training orchestration. Live agents are never updated here.
 */
class TrainingJobService
{
    public function __construct(
        private DatasetExporter $exporter,
        private TrainingWorkerClient $worker,
        private ModelRegistryService $registry,
    ) {}

    public function queue(
        AiTrainingDataset $dataset,
        ?User $actor = null,
        ?string $driver = null,
        ?string $baseModelKey = null,
        bool $requireDeidentified = true,
    ): AiTrainingJob {
        $dataset->loadMissing('items');

        if ((int) $dataset->item_count < 1 || $dataset->items->isEmpty()) {
            throw ValidationException::withMessages([
                'dataset' => ['Dataset has no items. Approve and build candidates first.'],
            ]);
        }

        if (! in_array($dataset->status, ['ready', 'exported'], true)) {
            throw ValidationException::withMessages([
                'dataset' => ['Dataset must be ready before training.'],
            ]);
        }

        if ($requireDeidentified) {
            $unsafe = $dataset->items->first(fn ($item) => ! (bool) data_get($item->meta, 'deidentified', false));
            if ($unsafe !== null) {
                throw ValidationException::withMessages([
                    'dataset' => ['Every dataset item must be marked de-identified before training.'],
                ]);
            }
        }

        $driver ??= (string) config('ai.training.driver', 'mock');
        if (! in_array($driver, ['mock', 'python'], true)) {
            throw ValidationException::withMessages([
                'driver' => ['Driver must be mock or python.'],
            ]);
        }

        $job = AiTrainingJob::query()->create([
            'tenant_id' => $dataset->tenant_id,
            'dataset_id' => $dataset->id,
            'requested_by' => $actor?->id,
            'driver' => $driver,
            'status' => AiTrainingJob::STATUS_QUEUED,
            'base_model_key' => $baseModelKey ?? (string) config('ai.training.base_model_key', 'mock-chat'),
            'meta' => ['weights_updated_in_production' => false],
        ]);

        RunTrainingJob::dispatch($job->id);

        return $job->fresh();
    }

    public function execute(AiTrainingJob $job): AiTrainingJob
    {
        $job->loadMissing('dataset.items');
        $dataset = $job->dataset;
        if ($dataset === null) {
            throw new \RuntimeException('Training job is missing its dataset.');
        }

        $job->forceFill([
            'status' => AiTrainingJob::STATUS_RUNNING,
            'started_at' => now(),
            'error_message' => null,
        ])->save();

        try {
            $exportRelative = $this->exporter->export($dataset);
            $resultRelative = sprintf('ai-training/%s/job-%s-result.json', $dataset->id, $job->id);

            $result = $this->worker->run(
                $job->driver,
                $this->exporter->absolutePath($exportRelative),
                $this->exporter->absolutePath($resultRelative),
            );

            if (($result['weights_updated_in_production'] ?? false) === true) {
                throw new \RuntimeException('Training must not update production weights.');
            }

            return DB::transaction(function () use ($job, $result, $exportRelative, $resultRelative): AiTrainingJob {
                $metrics = is_array($result['metrics'] ?? null) ? $result['metrics'] : [];

                $eval = AiEvalRun::query()->create([
                    'tenant_id' => $job->tenant_id,
                    'name' => 'Offline training eval #'.$job->id,
                    'status' => 'completed',
                    'overall_score' => $metrics['overall_score'] ?? null,
                    'groundedness' => $metrics['groundedness'] ?? null,
                    'safety' => $metrics['safety'] ?? 1.0,
                    'helpfulness' => $metrics['helpfulness'] ?? null,
                    'summary' => [
                        'source' => 'offline_training_worker',
                        'training_job_id' => $job->id,
                        'dataset_id' => $job->dataset_id,
                        'weights_updated_in_production' => false,
                        'lifecycle_suggested' => 'candidate',
                        'metrics' => $metrics,
                    ],
                ]);

                $version = $this->attachCandidateVersion($job, $result, $metrics);

                $job->forceFill([
                    'status' => AiTrainingJob::STATUS_SUCCEEDED,
                    'export_path' => $exportRelative,
                    'result_path' => $resultRelative,
                    'eval_run_id' => $eval->id,
                    'model_version_id' => $version->id,
                    'metrics' => $metrics,
                    'meta' => array_merge(is_array($job->meta) ? $job->meta : [], [
                        'weights_updated_in_production' => false,
                        'lifecycle' => $version->lifecycle_status,
                        'artifact' => $result['artifact'] ?? null,
                    ]),
                    'finished_at' => now(),
                ])->save();

                return $job->fresh(['dataset', 'evalRun', 'modelVersion']);
            });
        } catch (\Throwable $e) {
            $job->forceFill([
                'status' => AiTrainingJob::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'meta' => array_merge(is_array($job->meta) ? $job->meta : [], [
                    'weights_updated_in_production' => false,
                ]),
            ])->save();

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $metrics
     */
    private function attachCandidateVersion(AiTrainingJob $job, array $result, array $metrics): AiModelVersion
    {
        $baseKey = $job->base_model_key ?: 'mock-chat';
        $base = AiModel::query()->where('key', $baseKey)->first();
        $providerId = $base?->provider_id ?? AiProvider::query()->where('key', 'mock')->value('id');

        if ($providerId === null) {
            throw new \RuntimeException('No AI provider available to attach a candidate version.');
        }

        $candidateKey = 'ft-'.$job->dataset_id.'-'.$job->id;
        $model = AiModel::query()->firstOrCreate(
            ['provider_id' => $providerId, 'key' => $candidateKey],
            [
                'name' => 'Fine-tune candidate '.$candidateKey,
                'task_types' => $base?->task_types ?? ['general_conversation'],
                'is_custom' => true,
                'is_active' => false,
                'config' => ['origin' => 'offline_training', 'base_model_key' => $baseKey],
            ],
        );

        $version = AiModelVersion::query()->create([
            'model_id' => $model->id,
            'version' => 'c-'.now()->format('YmdHis'),
            'is_default' => false,
            'lifecycle_status' => 'base',
            'config' => [
                'origin' => 'offline_training',
                'training_job_id' => $job->id,
                'dataset_id' => $job->dataset_id,
            ],
        ]);

        return $this->registry->setLifecycle($version, 'candidate', [
            'source' => 'training_worker',
            'auto_promoted_to_production' => false,
            'metrics' => $metrics,
            'item_count' => $result['item_count'] ?? null,
        ]);
    }
}
