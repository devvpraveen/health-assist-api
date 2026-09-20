<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'tenant_id',
    'package_id',
    'status',
    'starts_at',
    'ends_at',
    'renews_at',
    'meta',
])]
class TenantSubscription extends Model
{
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PAST_DUE = 'past_due';

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected static function booted(): void
    {
        static::creating(function (TenantSubscription $subscription): void {
            if (empty($subscription->uuid)) {
                $subscription->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
