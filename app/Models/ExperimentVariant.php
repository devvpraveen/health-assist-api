<?php

namespace App\Models;

use Database\Factories\ExperimentVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $experiment_id
 * @property string $key
 * @property int $weight
 * @property array<string, mixed>|null $payload
 */
#[Fillable([
    'experiment_id',
    'key',
    'weight',
    'payload',
])]
class ExperimentVariant extends Model
{
    /** @use HasFactory<ExperimentVariantFactory> */
    use HasFactory;

    protected $attributes = [
        'weight' => 50,
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'payload' => 'array',
        ];
    }

    /** @return BelongsTo<Experiment, $this> */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }
}
