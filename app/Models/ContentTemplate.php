<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Dynamic content template (assessment, SOAP, notification, …).
 *
 * Platform rows use tenant_id=null / owner_key=platform. Tenant overrides use owner_key=tenant:{id}.
 * Do not use BelongsToTenant — platform rows must remain visible alongside tenant context.
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'owner_key',
    'key',
    'type',
    'module_key',
    'name',
    'description',
    'status',
    'active_version_id',
    'meta',
])]
class ContentTemplate extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'templates';

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted(): void
    {
        static::creating(function (ContentTemplate $template): void {
            if (empty($template->uuid)) {
                $template->uuid = (string) Str::uuid();
            }
            if (empty($template->owner_key)) {
                $template->owner_key = $template->tenant_id === null
                    ? 'platform'
                    : 'tenant:'.$template->tenant_id;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<TemplateVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class, 'template_id');
    }

    /**
     * @return BelongsTo<TemplateVersion, $this>
     */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class, 'active_version_id');
    }
}
