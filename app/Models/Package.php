<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $key
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property int $price_cents
 * @property string $currency
 * @property int|null $validity_days
 * @property array<string, mixed>|null $metadata
 */
#[Fillable([
    'uuid',
    'key',
    'name',
    'slug',
    'description',
    'sort_order',
    'is_active',
    'price_cents',
    'currency',
    'validity_days',
    'metadata',
])]
class Package extends Model
{
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
        'price_cents' => 0,
        'currency' => 'INR',
    ];

    protected static function booted(): void
    {
        static::creating(function (Package $package): void {
            if (empty($package->uuid)) {
                $package->uuid = (string) Str::uuid();
            }
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->key);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
            'price_cents' => 'integer',
            'validity_days' => 'integer',
        ];
    }

    /**
     * @return BelongsToMany<PlatformModule, $this>
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(PlatformModule::class, 'package_modules', 'package_id', 'module_id')
            ->withPivot(['inclusion'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PackageEntitlement, $this>
     */
    public function entitlements(): HasMany
    {
        return $this->hasMany(PackageEntitlement::class);
    }

    /**
     * @return HasMany<PackageLimit, $this>
     */
    public function limits(): HasMany
    {
        return $this->hasMany(PackageLimit::class);
    }

    /**
     * @return HasMany<TenantSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
