<?php

namespace App\Models;

use Database\Factories\BillingTaxRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property string $name
 * @property string $code
 * @property int $rate_bps
 * @property bool $is_inclusive
 * @property bool $is_active
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'name',
    'code',
    'rate_bps',
    'is_inclusive',
    'is_active',
])]
class BillingTaxRate extends Model
{
    /** @use HasFactory<BillingTaxRateFactory> */
    use HasFactory;

    protected $attributes = [
        'is_inclusive' => false,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (BillingTaxRate $taxRate): void {
            if (empty($taxRate->uuid)) {
                $taxRate->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate_bps' => 'integer',
            'is_inclusive' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleToTenant(Builder $query, ?int $tenantId): Builder
    {
        return $query->where(function (Builder $builder) use ($tenantId): void {
            $builder->whereNull('tenant_id');

            if ($tenantId !== null) {
                $builder->orWhere('tenant_id', $tenantId);
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
}
