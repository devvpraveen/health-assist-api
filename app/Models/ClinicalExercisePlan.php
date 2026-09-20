<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ClinicalExercisePlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $patient_id
 * @property int|null $treatment_plan_id
 * @property int|null $provider_id
 * @property string $title
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string $status
 * @property string|null $notes
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'treatment_plan_id',
    'provider_id',
    'title',
    'start_date',
    'end_date',
    'status',
    'notes',
])]
class ClinicalExercisePlan extends Model
{
    /** @use HasFactory<ClinicalExercisePlanFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalExercisePlan $plan): void {
            if (empty($plan->uuid)) {
                $plan->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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
     * @return BelongsTo<ClinicalTreatmentPlan, $this>
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(ClinicalTreatmentPlan::class, 'treatment_plan_id');
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @return HasMany<ClinicalExercisePlanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ClinicalExercisePlanItem::class, 'exercise_plan_id')->orderBy('sort_order');
    }

    /**
     * @return HasManyThrough<ClinicalExerciseLog, ClinicalExercisePlanItem, $this>
     */
    public function logs(): HasManyThrough
    {
        return $this->hasManyThrough(
            ClinicalExerciseLog::class,
            ClinicalExercisePlanItem::class,
            'exercise_plan_id',
            'exercise_plan_item_id',
        );
    }
}
