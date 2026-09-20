<?php

namespace App\Models;

use Database\Factories\SpecialtyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property string $tenant_key
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'tenant_key',
    'name',
    'slug',
    'description',
    'status',
])]
class Specialty extends Model
{
    /** @use HasFactory<SpecialtyFactory> */
    use HasFactory;

    public const STATUSES = ['active', 'inactive'];

    protected $attributes = [
        'status' => 'active',
        'tenant_key' => 'system',
    ];

    protected static function booted(): void
    {
        static::creating(function (Specialty $specialty): void {
            if (empty($specialty->uuid)) {
                $specialty->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (Specialty $specialty): void {
            $specialty->tenant_key = $specialty->tenant_id === null
                ? 'system'
                : (string) $specialty->tenant_id;
        });
    }

    /**
     * System catalog (tenant_id null) plus current tenant specialties when context is set.
     *
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

    /**
     * @return BelongsToMany<Clinic, $this>
     */
    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Provider, $this>
     */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class)
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }
}
