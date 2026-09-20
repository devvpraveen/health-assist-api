<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiTrainingDataset extends Model
{
    protected $fillable = [
        'tenant_id',
        'key',
        'name',
        'version',
        'agent',
        'status',
        'item_count',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'item_count' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AiTrainingDatasetItem::class, 'dataset_id');
    }
}
