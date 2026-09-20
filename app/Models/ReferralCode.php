<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ReferralCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property string $code
 * @property int|null $owner_user_id
 * @property int|null $owner_patient_id
 * @property string|null $campaign
 * @property bool $is_active
 * @property int|null $max_uses
 * @property int $uses_count
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'code',
    'owner_user_id',
    'owner_patient_id',
    'campaign',
    'is_active',
    'max_uses',
    'uses_count',
])]
class ReferralCode extends Model
{
    /** @use HasFactory<ReferralCodeFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'is_active' => true,
        'uses_count' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (ReferralCode $code): void {
            if (empty($code->uuid)) {
                $code->uuid = (string) Str::uuid();
            }
            if (empty($code->code)) {
                $code->code = strtoupper(Str::random(8));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
        ];
    }

    public function hasCapacity(): bool
    {
        if ($this->max_uses === null) {
            return true;
        }

        return $this->uses_count < $this->max_uses;
    }

    /** @return BelongsTo<User, $this> */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** @return BelongsTo<Patient, $this> */
    public function ownerPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'owner_patient_id');
    }

    /** @return HasMany<Referral, $this> */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_code_id');
    }
}
