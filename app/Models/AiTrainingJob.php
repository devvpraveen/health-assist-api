<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'tenant_id',
    'dataset_id',
    'eval_run_id',
    'model_version_id',
    'requested_by',
    'driver',
    'status',
    'base_model_key',
    'export_path',
    'result_path',
    'error_message',
    'metrics',
    'meta',
    'started_at',
    'finished_at',
])]
class AiTrainingJob extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $attributes = [
        'status' => self::STATUS_QUEUED,
    ];

    protected static function booted(): void
    {
        static::creating(function (AiTrainingJob $job): void {
            if (empty($job->uuid)) {
                $job->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'meta' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AiTrainingDataset, $this>
     */
    public function dataset(): BelongsTo
    {
        return $this->belongsTo(AiTrainingDataset::class, 'dataset_id');
    }

    /**
     * @return BelongsTo<AiEvalRun, $this>
     */
    public function evalRun(): BelongsTo
    {
        return $this->belongsTo(AiEvalRun::class, 'eval_run_id');
    }

    /**
     * @return BelongsTo<AiModelVersion, $this>
     */
    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(AiModelVersion::class, 'model_version_id');
    }
}
