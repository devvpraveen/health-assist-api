<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $channel
 * @property string $destination
 * @property string $code_hash
 * @property int|null $tenant_id
 * @property int $attempts
 * @property Carbon $expires_at
 * @property Carbon|null $consumed_at
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'channel',
    'destination',
    'code_hash',
    'tenant_id',
    'attempts',
    'expires_at',
    'consumed_at',
    'meta',
])]
class OtpChallenge extends Model
{
    public const CHANNEL_MOBILE = 'mobile';

    public const CHANNEL_EMAIL = 'email';

    protected static function booted(): void
    {
        static::creating(function (OtpChallenge $challenge): void {
            if (empty($challenge->uuid)) {
                $challenge->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
