<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $provider_id
 * @property string $key
 * @property string $name
 * @property list<string>|null $task_types
 * @property array<string, mixed>|null $config
 * @property bool $is_custom
 * @property string|null $external_model_id
 * @property bool $is_active
 */
#[Fillable(['provider_id', 'key', 'name', 'task_types', 'config', 'is_custom', 'external_model_id', 'is_active'])]
class AiModel extends Model
{
    protected $casts = [
        'task_types' => 'array',
        'config' => 'array',
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<AiProvider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'provider_id');
    }

    /**
     * @return HasMany<AiModelVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AiModelVersion::class, 'model_id');
    }
}
