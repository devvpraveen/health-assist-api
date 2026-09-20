<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string|null $anonymous_id
 * @property int $tenant_id
 * @property int|null $conversation_id
 * @property Carbon $expires_at
 * @property Carbon|null $claimed_at
 * @property int|null $claimed_by_user_id
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'anonymous_id',
    'tenant_id',
    'conversation_id',
    'expires_at',
    'claimed_at',
    'claimed_by_user_id',
    'meta',
])]
class GuestSession extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        static::creating(function (GuestSession $session): void {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
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
            'claimed_at' => 'datetime',
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

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    public function isUsable(): bool
    {
        return ! $this->isExpired() && ! $this->isClaimed();
    }

    /**
     * @return BelongsTo<HealthGuideConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(HealthGuideConversation::class, 'conversation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }
}
