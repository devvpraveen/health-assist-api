<?php

namespace App\Actions\Marketing;

use App\Models\Experiment;
use App\Models\ExperimentVariant;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateExperimentAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Experiment $experiment, array $data): Experiment
    {
        return DB::transaction(function () use ($experiment, $data): Experiment {
            $experiment->fill(collect($data)->only(['name', 'status', 'key'])->all());
            $experiment->save();

            if (isset($data['variants']) && is_array($data['variants'])) {
                $experiment->variants()->delete();
                foreach ($data['variants'] as $variant) {
                    ExperimentVariant::query()->create([
                        'experiment_id' => $experiment->id,
                        'key' => $variant['key'],
                        'weight' => $variant['weight'] ?? 50,
                        'payload' => $variant['payload'] ?? null,
                    ]);
                }
            }

            $this->auditLogger->log('marketing.experiment.updated', $experiment, [
                'key' => $experiment->key,
                'status' => $experiment->status,
            ]);

            return $experiment->refresh()->load('variants');
        });
    }
}
