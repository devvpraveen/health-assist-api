<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'run_id',
    'step_index',
    'step_type',
    'status',
    'input',
    'output',
    'error_message',
    'started_at',
    'finished_at',
])]
class AutomationWorkflowRunStep extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_FAILED = 'failed';

    public const STATUS_WAITING = 'waiting';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AutomationWorkflowRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflowRun::class, 'run_id');
    }
}
