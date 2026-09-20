<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'workflow_id',
    'version',
    'status',
    'label',
    'steps',
    'created_by_user_id',
    'published_at',
])]
class AutomationWorkflowVersion extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationWorkflowVersion $version): void {
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
            'steps' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AutomationWorkflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'workflow_id');
    }
}
