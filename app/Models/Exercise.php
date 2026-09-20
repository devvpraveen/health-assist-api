<?php

namespace App\Models;

use Database\Factories\ExerciseFactory;
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
 * @property string $tenant_key
 * @property string $name
 * @property string $slug
 * @property string|null $category
 * @property string|null $instructions
 * @property string|null $contraindications
 * @property int|null $default_duration_seconds
 * @property int|null $default_sets
 * @property int|null $default_reps
 * @property string|null $difficulty
 * @property string|null $media_url
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'tenant_key',
    'name',
    'slug',
    'category',
    'instructions',
    'contraindications',
    'default_duration_seconds',
    'default_sets',
    'default_reps',
    'difficulty',
    'media_url',
    'status',
])]
class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    public const STATUSES = ['active', 'inactive'];

    protected $attributes = [
        'status' => 'active',
        'tenant_key' => 'system',
    ];

    protected static function booted(): void
    {
        static::creating(function (Exercise $exercise): void {
            if (empty($exercise->uuid)) {
                $exercise->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (Exercise $exercise): void {
            $exercise->tenant_key = $exercise->tenant_id === null
                ? 'system'
                : (string) $exercise->tenant_id;
        });
    }

    /**
     * System catalog (tenant_id null) plus current tenant exercises when context is set.
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
}
