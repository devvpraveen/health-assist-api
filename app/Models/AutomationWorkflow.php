<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'tenant_id',
    'owner_key',
    'key',
    'name',
    'description',
    'trigger',
    'module_key',
    'status',
    'is_active',
    'active_version_id',
    'meta',
])]
class AutomationWorkflow extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'is_active' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationWorkflow $workflow): void {
            if (empty($workflow->uuid)) {
                $workflow->uuid = (string) Str::uuid();
            }
            if (empty($workflow->owner_key)) {
                $workflow->owner_key = $workflow->tenant_id === null
                    ? 'platform'
                    : 'tenant:'.$workflow->tenant_id;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<AutomationWorkflowVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AutomationWorkflowVersion::class, 'workflow_id');
    }

    /**
     * @return BelongsTo<AutomationWorkflowVersion, $this>
     */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflowVersion::class, 'active_version_id');
    }

    /**
     * @return HasMany<AutomationWorkflowRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AutomationWorkflowRun::class, 'workflow_id');
    }
}
