<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\ClinicalDocumentWorkflow;
use Database\Factories\ClinicalSoapNoteFactory;
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
 * @property int $provider_id
 * @property int|null $clinic_id
 * @property int|null $appointment_id
 * @property int|null $assessment_id
 * @property string|null $subjective
 * @property string|null $objective
 * @property string|null $assessment
 * @property string|null $plan
 * @property Carbon $session_date
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
    'assessment_id',
    'subjective',
    'objective',
    'assessment',
    'plan',
    'session_date',
    'status',
    'source',
    'authored_by_user_id',
    'approved_by_user_id',
    'approved_at',
])]
class ClinicalSoapNote extends Model
{
    /** @use HasFactory<ClinicalSoapNoteFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
        'source' => ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalSoapNote $note): void {
            if (empty($note->uuid)) {
                $note->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_date' => 'datetime',
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
     * @return BelongsTo<ClinicalAssessment, $this>
     */
    public function clinicalAssessment(): BelongsTo
    {
        return $this->belongsTo(ClinicalAssessment::class, 'assessment_id');
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
