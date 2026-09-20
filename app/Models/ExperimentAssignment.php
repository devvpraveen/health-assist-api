<?php

namespace App\Models;

use Database\Factories\ExperimentAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $experiment_id
 * @property string|null $anonymous_id
 * @property int|null $user_id
 * @property string $variant_key
 * @property Carbon $assigned_at
 */
#[Fillable([
    'experiment_id',
    'anonymous_id',
    'user_id',
    'variant_key',
    'assigned_at',
])]
class ExperimentAssignment extends Model
{
    /** @use HasFactory<ExperimentAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Experiment, $this> */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }
}
