<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string $status
 * @property string|null $plan_code
 */
#[Fillable(['uuid', 'name', 'slug', 'status', 'plan_code'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (empty($tenant->uuid)) {
                $tenant->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasOne<TenantSetting, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(TenantSetting::class);
    }

    /**
     * @return HasOne<TenantBranding, $this>
     */
    public function branding(): HasOne
    {
        return $this->hasOne(TenantBranding::class);
    }

    /**
     * @return HasMany<TenantDomain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }
}
