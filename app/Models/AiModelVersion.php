<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $model_id
 * @property string $version
 * @property bool $is_default
 * @property string $lifecycle_status
 * @property array<string, mixed>|null $config
 * @property array<string, mixed>|null $lifecycle_meta
 */
#[Fillable(['model_id', 'version', 'is_default', 'config', 'lifecycle_status', 'lifecycle_meta'])]
class AiModelVersion extends Model
{
    protected $casts = [
        'is_default' => 'boolean',
        'config' => 'array',
        'lifecycle_meta' => 'array',
    ];

    /**
     * @return BelongsTo<AiModel, $this>
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'model_id');
    }
}
