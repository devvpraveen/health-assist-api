<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $actor_user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 */
#[Fillable([
    'tenant_id',
    'actor_user_id',
    'action',
    'subject_type',
    'subject_id',
    'ip',
    'user_agent',
    'meta',
])]
class AuditLog extends Model
{
    public $timestamps = false;

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuditLog $log): void {
            if ($log->created_at === null) {
                $log->created_at = now();
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
