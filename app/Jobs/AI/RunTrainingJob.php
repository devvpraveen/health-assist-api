<?php

namespace App\Jobs\AI;

use App\Models\AiTrainingJob;
use App\Services\AI\Learning\TrainingJobService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunTrainingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $jobId) {}

    public function handle(TrainingJobService $service): void
    {
        $job = AiTrainingJob::query()->find($this->jobId);
        if ($job === null) {
            return;
        }

        try {
            $service->execute($job);
        } catch (Throwable) {
            // Status/error already persisted by TrainingJobService::execute.
        }
    }
}
