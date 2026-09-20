<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ClinicalExerciseLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $patient_id
 * @property int $exercise_plan_item_id
 * @property Carbon $performed_at
 * @property string $result
 * @property string|null $notes
 * @property int|null $pain_score
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'exercise_plan_item_id',
    'performed_at',
    'result',
    'notes',
    'pain_score',
])]
class ClinicalExerciseLog extends Model
{
    /** @use HasFactory<ClinicalExerciseLogFactory> */
    use BelongsToTenant, HasFactory;

    public const RESULT_COMPLETED = 'completed';

    public const RESULT_SKIPPED = 'skipped';

    public const RESULT_PAINFUL = 'painful';

    public const RESULT_DIFFICULT = 'difficult';

    public const RESULTS = [
        self::RESULT_COMPLETED,
        self::RESULT_SKIPPED,
        self::RESULT_PAINFUL,
        self::RESULT_DIFFICULT,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalExerciseLog $log): void {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<ClinicalExercisePlanItem, $this>
     */
    public function exercisePlanItem(): BelongsTo
    {
        return $this->belongsTo(ClinicalExercisePlanItem::class, 'exercise_plan_item_id');
    }
}
