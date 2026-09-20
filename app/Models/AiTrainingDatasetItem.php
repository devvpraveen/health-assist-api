<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrainingDatasetItem extends Model
{
    protected $fillable = [
        'dataset_id',
        'candidate_id',
        'agent',
        'prompt_redacted',
        'completion_redacted',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(AiTrainingDataset::class, 'dataset_id');
    }
}
