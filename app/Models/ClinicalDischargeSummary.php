<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\ClinicalDocumentWorkflow;
use Database\Factories\ClinicalDischargeSummaryFactory;
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
 * @property int|null $provider_id
 * @property int|null $clinic_id
 * @property int|null $treatment_plan_id
 * @property Carbon $discharged_at
 * @property string|null $reason
 * @property string|null $initial_condition
 * @property string|null $treatment_provided
 * @property string|null $progress_summary
 * @property string|null $current_status
 * @property string|null $home_program
 * @property string|null $follow_up
 * @property string|null $referral
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
    'treatment_plan_id',
    'discharged_at',
    'reason',
    'initial_condition',
    'treatment_provided',
    'progress_summary',
    'current_status',
    'home_program',
    'follow_up',
    'referral',
    'status',
    'source',
    'authored_by_user_id',
    'approved_by_user_id',
    'approved_at',
])]
class ClinicalDischargeSummary extends Model
{
    /** @use HasFactory<ClinicalDischargeSummaryFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
        'source' => ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalDischargeSummary $summary): void {
            if (empty($summary->uuid)) {
                $summary->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discharged_at' => 'datetime',
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
     * @return BelongsTo<ClinicalTreatmentPlan, $this>
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(ClinicalTreatmentPlan::class, 'treatment_plan_id');
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
