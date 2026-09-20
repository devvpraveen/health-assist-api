<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\EmailWorkflowRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property int $workflow_id
 * @property int $subscription_id
 * @property string $status
 * @property int $current_step
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'workflow_id',
    'subscription_id',
    'status',
    'current_step',
    'meta',
])]
class EmailWorkflowRun extends Model
{
    /** @use HasFactory<EmailWorkflowRunFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SKIPPED = 'skipped';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'current_step' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (EmailWorkflowRun $run): void {
            if (empty($run->uuid)) {
                $run->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'current_step' => 'integer',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<EmailWorkflow, $this> */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(EmailWorkflow::class, 'workflow_id');
    }

    /** @return BelongsTo<EmailSubscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(EmailSubscription::class, 'subscription_id');
    }
}
