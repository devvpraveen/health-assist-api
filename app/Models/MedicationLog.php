<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MedicationLogFactory;
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
 * @property int $medication_id
 * @property int $patient_id
 * @property int|null $schedule_id
 * @property Carbon|null $scheduled_for
 * @property Carbon $logged_at
 * @property string $status
 * @property string|null $notes
 * @property int|null $logged_by_user_id
 * @property string|null $idempotency_key
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'medication_id',
    'patient_id',
    'schedule_id',
    'scheduled_for',
    'logged_at',
    'status',
    'notes',
    'logged_by_user_id',
    'idempotency_key',
])]
class MedicationLog extends Model
{
    /** @use HasFactory<MedicationLogFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_TAKEN = 'taken';

    public const STATUS_MISSED = 'missed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [
        self::STATUS_TAKEN,
        self::STATUS_MISSED,
        self::STATUS_SKIPPED,
    ];

    protected static function booted(): void
    {
        static::creating(function (MedicationLog $log): void {
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
            'scheduled_for' => 'datetime',
            'logged_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Medication, $this>
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<MedicationSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MedicationSchedule::class, 'schedule_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by_user_id');
    }
}
