<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $agent
 * @property string $model
 * @property string|null $model_version
 * @property string|null $prompt_key
 * @property int|null $prompt_version
 * @property string $input_type
 * @property string|null $input_redacted
 * @property string|null $output_redacted
 * @property string $review_status
 * @property int|null $reviewer_user_id
 * @property int|null $usage_record_id
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
#[Fillable([
    'tenant_id',
    'user_id',
    'agent',
    'model',
    'model_version',
    'prompt_key',
    'prompt_version',
    'input_type',
    'input_redacted',
    'output_redacted',
    'review_status',
    'reviewer_user_id',
    'usage_record_id',
    'meta',
    'created_at',
])]
class AiAuditLog extends Model
{
    use BelongsToTenant;

    public const REVIEW_PENDING = 'pending';

    public const REVIEW_APPROVED = 'approved';

    public const REVIEW_REJECTED = 'rejected';

    public const REVIEW_NOT_REQUIRED = 'not_required';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
        'meta' => 'array',
        'prompt_version' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiAuditLog $log): void {
            if ($log->created_at === null) {
                $log->created_at = now();
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<AiUsageRecord, $this>
     */
    public function usageRecord(): BelongsTo
    {
        return $this->belongsTo(AiUsageRecord::class, 'usage_record_id');
    }
}
