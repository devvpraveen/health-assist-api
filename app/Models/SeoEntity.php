<?php

namespace App\Models;

use Database\Factories\SeoEntityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property string $tenant_key
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property string $summary
 * @property string|null $body
 * @property string $locale
 * @property int|null $parent_entity_id
 * @property string $status
 * @property int|null $author_user_id
 * @property int|null $reviewer_user_id
 * @property Carbon|null $last_reviewed_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $canonical_path
 * @property string|null $schema_type
 * @property list<string>|array<string, mixed>|null $structured_facts
 * @property string|null $direct_answer
 * @property list<mixed>|null $citations
 * @property Carbon|null $published_at
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'tenant_key',
    'type',
    'title',
    'slug',
    'summary',
    'body',
    'locale',
    'parent_entity_id',
    'status',
    'author_user_id',
    'reviewer_user_id',
    'last_reviewed_at',
    'seo_title',
    'seo_description',
    'canonical_path',
    'schema_type',
    'structured_facts',
    'direct_answer',
    'citations',
    'published_at',
])]
class SeoEntity extends Model
{
    /** @use HasFactory<SeoEntityFactory> */
    use HasFactory;

    public const TYPE_CONDITION = 'condition';

    public const TYPE_SYMPTOM = 'symptom';

    public const TYPE_TREATMENT = 'treatment';

    public const TYPE_SPECIALTY = 'specialty';

    public const TYPE_SERVICE = 'service';

    public const TYPE_LOCATION = 'location';

    public const TYPE_GLOSSARY = 'glossary';

    public const TYPE_FAQ_TOPIC = 'faq_topic';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public const RELATION_RELATED = 'related';

    public const RELATION_SYMPTOM_OF = 'symptom_of';

    public const RELATION_TREATS = 'treats';

    public const RELATION_SPECIALTY_FOR = 'specialty_for';

    public const RELATION_FAQ_FOR = 'faq_for';

    protected $attributes = [
        'tenant_key' => 'system',
        'locale' => 'en',
        'status' => self::STATUS_DRAFT,
        'citations' => '[]',
    ];

    protected static function booted(): void
    {
        static::creating(function (SeoEntity $entity): void {
            if (empty($entity->uuid)) {
                $entity->uuid = (string) Str::uuid();
            }
        });

        static::saving(function (SeoEntity $entity): void {
            $entity->tenant_key = $entity->tenant_id === null
                ? 'system'
                : (string) $entity->tenant_id;

            if ($entity->citations === null) {
                $entity->citations = [];
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'structured_facts' => 'array',
            'citations' => 'array',
            'last_reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function isReviewed(): bool
    {
        return $this->reviewer_user_id !== null && $this->last_reviewed_at !== null;
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
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<SeoEntity, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_entity_id');
    }

    /**
     * @return HasMany<SeoEntity, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_entity_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    /**
     * @return HasMany<SeoFaq, $this>
     */
    public function faqs(): HasMany
    {
        return $this->hasMany(SeoFaq::class, 'entity_id');
    }

    /**
     * @return BelongsToMany<SeoEntity, $this>
     */
    public function relatedEntities(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'seo_entity_relations',
            'from_entity_id',
            'to_entity_id',
        )->withPivot('relation')->withTimestamps();
    }
}
