<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\ClinicalDocumentWorkflow;
use Database\Factories\ClinicalProgressNoteFactory;
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
 * @property int|null $treatment_plan_id
 * @property int|null $appointment_id
 * @property Carbon $noted_at
 * @property string $note
 * @property array<string, mixed>|null $measurements
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
    'treatment_plan_id',
    'appointment_id',
    'noted_at',
    'note',
    'measurements',
    'status',
    'source',
    'authored_by_user_id',
    'approved_by_user_id',
    'approved_at',
])]
class ClinicalProgressNote extends Model
{
    /** @use HasFactory<ClinicalProgressNoteFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'status' => ClinicalDocumentWorkflow::STATUS_DRAFT,
        'source' => ClinicalDocumentWorkflow::SOURCE_CLINICIAN,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalProgressNote $note): void {
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
            'noted_at' => 'datetime',
            'measurements' => 'array',
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
     * @return BelongsTo<ClinicalTreatmentPlan, $this>
     */
    public function treatmentPlan(): BelongsTo
    {
        return $this->belongsTo(ClinicalTreatmentPlan::class, 'treatment_plan_id');
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
