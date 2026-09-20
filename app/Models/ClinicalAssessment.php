<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\ClinicalDocumentWorkflow;
use Database\Factories\ClinicalAssessmentFactory;
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
 * @property int|null $appointment_id
 * @property string|null $template_key
 * @property Carbon $assessed_at
 * @property string|null $chief_complaint
 * @property array<string, mixed>|null $findings
 * @property string|null $summary
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
    'appointment_id',
    'template_key',
    'assessed_at',
    'chief_complaint',
    'findings',
    'summary',
    'status',
    'source',
    'authored_by_user_id',
    'approved_by_user_id',
    'approved_at',
])]
class ClinicalAssessment extends Model
{
    /** @use HasFactory<ClinicalAssessmentFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
        'source' => ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalAssessment $assessment): void {
            if (empty($assessment->uuid)) {
                $assessment->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'findings' => 'array',
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
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
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
