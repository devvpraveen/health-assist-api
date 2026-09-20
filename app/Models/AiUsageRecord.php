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
 * @property string $feature
 * @property string $model
 * @property string|null $model_version
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $latency_ms
 * @property string $status
 * @property int|null $estimated_cost_cents
 * @property string $request_id
 * @property Carbon $created_at
 */
#[Fillable([
    'tenant_id',
    'user_id',
    'agent',
    'feature',
    'model',
    'model_version',
    'input_tokens',
    'output_tokens',
    'latency_ms',
    'status',
    'estimated_cost_cents',
    'request_id',
    'created_at',
])]
class AiUsageRecord extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'latency_ms' => 'integer',
        'estimated_cost_cents' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (AiUsageRecord $record): void {
            if ($record->created_at === null) {
                $record->created_at = now();
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
}
