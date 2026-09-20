<?php

namespace App\Models;

use Database\Factories\WellnessContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property string $tenant_key
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $summary
 * @property string $body
 * @property string $locale
 * @property bool $is_clinical_advice
 * @property string $status
 * @property int|null $author_user_id
 * @property Carbon|null $published_at
 * @property list<string>|null $personalization_tags
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'tenant_key',
    'category_id',
    'title',
    'slug',
    'summary',
    'body',
    'locale',
    'is_clinical_advice',
    'status',
    'author_user_id',
    'published_at',
    'personalization_tags',
])]
class WellnessContent extends Model
{
    /** @use HasFactory<WellnessContentFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    protected $attributes = [
        'tenant_key' => 'system',
        'locale' => 'en',
        'is_clinical_advice' => false,
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted(): void
    {
        static::creating(function (WellnessContent $content): void {
            if (empty($content->uuid)) {
                $content->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (WellnessContent $content): void {
            $content->tenant_key = $content->tenant_id === null
                ? 'system'
                : (string) $content->tenant_id;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_clinical_advice' => 'boolean',
            'published_at' => 'datetime',
            'personalization_tags' => 'array',
        ];
    }

    /**
     * Platform catalog plus current tenant content when context is set.
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
    public function scopeWellnessOnly(Builder $query): Builder
    {
        return $query->where('is_clinical_advice', false);
    }

    /**
     * @return BelongsTo<WellnessCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(WellnessCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
