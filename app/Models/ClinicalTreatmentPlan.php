<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\ClinicalDocumentWorkflow;
use Database\Factories\ClinicalTreatmentPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $patient_id
 * @property int|null $provider_id
 * @property int|null $clinic_id
 * @property string $title
 * @property string|null $diagnosis_summary
 * @property array<int|string, mixed>|null $goals
 * @property string|null $frequency
 * @property int|null $duration_weeks
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $reassessment_date
 * @property string|null $home_program_notes
 * @property string $status
 * @property string $source
 * @property int $authored_by_user_id
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'provider_id',
    'clinic_id',
    'title',
    'diagnosis_summary',
    'goals',
    'frequency',
    'duration_weeks',
    'start_date',
    'end_date',
    'reassessment_date',
    'home_program_notes',
    'status',
    'source',
    'authored_by_user_id',
    'approved_by_user_id',
    'approved_at',
])]
class ClinicalTreatmentPlan extends Model
{
    /** @use HasFactory<ClinicalTreatmentPlanFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
        'source' => ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalTreatmentPlan $plan): void {
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
            'goals' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'reassessment_date' => 'date',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * @return HasMany<ClinicalTreatmentSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClinicalTreatmentSession::class, 'treatment_plan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function authoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authored_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
