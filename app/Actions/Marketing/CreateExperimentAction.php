<?php

namespace App\Actions\Marketing;

use App\Models\Experiment;
use App\Models\ExperimentVariant;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateExperimentAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Experiment
    {
        return DB::transaction(function () use ($data): Experiment {
            $tenantId = array_key_exists('tenant_id', $data)
                ? $data['tenant_id']
                : TenantContext::id();

            $experiment = Experiment::query()->create([
                'tenant_id' => $tenantId,
                'tenant_key' => $tenantId ? 'tenant:'.$tenantId : 'system',
                'key' => $data['key'] ?? Str::slug($data['name']),
                'name' => $data['name'],
                'status' => $data['status'] ?? Experiment::STATUS_DRAFT,
            ]);

            foreach ($data['variants'] ?? [] as $variant) {
                ExperimentVariant::query()->create([
                    'experiment_id' => $experiment->id,
                    'key' => $variant['key'],
                    'weight' => $variant['weight'] ?? 50,
                    'payload' => $variant['payload'] ?? null,
                ]);
            }

            $this->auditLogger->log('marketing.experiment.created', $experiment, [
                'key' => $experiment->key,
            ]);

            return $experiment->load('variants');
        });
    }
}
