<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ReferralFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $referrer_code_id
 * @property string|null $referred_anonymous_id
 * @property int|null $referred_user_id
 * @property int|null $referred_patient_id
 * @property string $status
 * @property Carbon|null $converted_at
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'referrer_code_id',
    'referred_anonymous_id',
    'referred_user_id',
    'referred_patient_id',
    'status',
    'converted_at',
    'meta',
])]
class Referral extends Model
{
    /** @use HasFactory<ReferralFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONVERTED = 'converted';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONVERTED,
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (Referral $referral): void {
            if (empty($referral->uuid)) {
                $referral->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<ReferralCode, $this> */
    public function referrerCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referrer_code_id');
    }

    /** @return HasMany<ReferralReward, $this> */
    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }
}
