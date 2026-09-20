<?php

namespace App\Models;

use Database\Factories\ClinicalExercisePlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $exercise_plan_id
 * @property int|null $exercise_id
 * @property string|null $custom_name
 * @property string|null $frequency
 * @property int|null $sets
 * @property int|null $reps
 * @property int|null $duration_seconds
 * @property string|null $instructions
 * @property int $sort_order
 */
#[Fillable([
    'exercise_plan_id',
    'exercise_id',
    'custom_name',
    'frequency',
    'sets',
    'reps',
    'duration_seconds',
    'instructions',
    'sort_order',
])]
class ClinicalExercisePlanItem extends Model
{
    /** @use HasFactory<ClinicalExercisePlanItemFactory> */
    use HasFactory;

    protected $attributes = [
        'sort_order' => 0,
    ];

    /**
     * @return BelongsTo<ClinicalExercisePlan, $this>
     */
    public function exercisePlan(): BelongsTo
    {
        return $this->belongsTo(ClinicalExercisePlan::class, 'exercise_plan_id');
    }

    /**
     * @return BelongsTo<Exercise, $this>
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * @return HasMany<ClinicalExerciseLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ClinicalExerciseLog::class, 'exercise_plan_item_id');
    }
}
