<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ReferralRewardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $referral_id
 * @property string $type
 * @property string $status
 * @property int|null $amount_cents
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'referral_id',
    'type',
    'status',
    'amount_cents',
])]
class ReferralReward extends Model
{
    /** @use HasFactory<ReferralRewardFactory> */
    use BelongsToTenant, HasFactory;

    public const TYPE_CREDIT = 'credit';

    public const TYPE_BADGE = 'badge';

    public const TYPE_NONE = 'none';

    public const STATUS_PENDING = 'pending';

    public const STATUS_GRANTED = 'granted';

    protected $attributes = [
        'type' => self::TYPE_NONE,
        'status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (ReferralReward $reward): void {
            if (empty($reward->uuid)) {
                $reward->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Referral, $this> */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }
}
