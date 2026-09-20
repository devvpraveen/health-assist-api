<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $template_id
 * @property int $version
 * @property string $status
 * @property string|null $label
 * @property array<string, mixed> $schema
 * @property int|null $created_by_user_id
 * @property Carbon|null $published_at
 */
#[Fillable([
    'uuid',
    'template_id',
    'version',
    'status',
    'label',
    'schema',
    'created_by_user_id',
    'published_at',
])]
class TemplateVersion extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted(): void
    {
        static::creating(function (TemplateVersion $version): void {
            if (empty($version->uuid)) {
                $version->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContentTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ContentTemplate::class, 'template_id');
    }
}
