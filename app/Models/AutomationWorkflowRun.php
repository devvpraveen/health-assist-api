<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'tenant_id',
    'workflow_id',
    'workflow_version_id',
    'trigger',
    'status',
    'current_step',
    'context',
    'error_message',
    'started_at',
    'finished_at',
])]
class AutomationWorkflowRun extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SKIPPED = 'skipped';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'current_step' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationWorkflowRun $run): void {
            if (empty($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AutomationWorkflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'workflow_id');
    }

    /**
     * @return BelongsTo<AutomationWorkflowVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflowVersion::class, 'workflow_version_id');
    }

    /**
     * @return HasMany<AutomationWorkflowRunStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(AutomationWorkflowRunStep::class, 'run_id');
    }
}
