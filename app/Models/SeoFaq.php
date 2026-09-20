<?php

namespace App\Models;

use Database\Factories\SeoFaqFactory;
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
 * @property int|null $entity_id
 * @property string $question
 * @property string $answer
 * @property string $locale
 * @property int $sort_order
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'tenant_key',
    'entity_id',
    'question',
    'answer',
    'locale',
    'sort_order',
    'status',
])]
class SeoFaq extends Model
{
    /** @use HasFactory<SeoFaqFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $attributes = [
        'tenant_key' => 'system',
        'locale' => 'en',
        'sort_order' => 0,
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted(): void
    {
        static::creating(function (SeoFaq $faq): void {
            if (empty($faq->uuid)) {
                $faq->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (SeoFaq $faq): void {
            $faq->tenant_key = $faq->tenant_id === null
                ? 'system'
                : (string) $faq->tenant_id;
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
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
     * @return BelongsTo<SeoEntity, $this>
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(SeoEntity::class, 'entity_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
