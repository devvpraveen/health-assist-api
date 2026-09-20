<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $driver
 * @property array<string, mixed>|null $config
 * @property bool $is_active
 */
#[Fillable(['key', 'name', 'driver', 'config', 'is_active'])]
class AiProvider extends Model
{
    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<AiModel, $this>
     */
    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class, 'provider_id');
    }
}
