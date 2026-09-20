<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ClinicalTreatmentSessionFactory;
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
 * @property int|null $treatment_plan_id
 * @property int|null $appointment_id
 * @property int|null $provider_id
 * @property Carbon $session_at
 * @property string|null $modality
 * @property string|null $interventions
 * @property string|null $patient_response
 * @property int|null $duration_minutes
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'treatment_plan_id',
    'appointment_id',
    'provider_id',
    'session_at',
    'modality',
    'interventions',
    'patient_response',
    'duration_minutes',
    'status',
])]
class ClinicalTreatmentSession extends Model
{
    /** @use HasFactory<ClinicalTreatmentSessionFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_MISSED = 'missed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_COMPLETED,
        self::STATUS_MISSED,
        self::STATUS_CANCELLED,
    ];

    protected $attributes = [
        'status' => self::STATUS_SCHEDULED,
    ];

    protected static function booted(): void
    {
        static::creating(function (ClinicalTreatmentSession $session): void {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_at' => 'datetime',
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
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
