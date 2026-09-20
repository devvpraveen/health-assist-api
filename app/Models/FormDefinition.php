<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Platform rows use tenant_id=null. Do not use BelongsToTenant so platform defs stay visible.
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
class FormDefinition extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted(): void
    {
        static::creating(function (FormDefinition $form): void {
            if (empty($form->uuid)) {
                $form->uuid = (string) Str::uuid();
            }
            if (empty($form->owner_key)) {
                $form->owner_key = $form->tenant_id === null
                    ? 'platform'
                    : 'tenant:'.$form->tenant_id;
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
     * @return HasMany<FormVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(FormVersion::class);
    }

    /**
     * @return BelongsTo<FormVersion, $this>
     */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(FormVersion::class, 'active_version_id');
    }

    /**
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
