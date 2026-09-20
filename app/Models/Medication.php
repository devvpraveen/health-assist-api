<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MedicationFactory;
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
 * @property string $name
 * @property string $dosage
 * @property string|null $frequency_label
 * @property string|null $route
 * @property string|null $instructions
 * @property string|null $notes
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string $status
 * @property int|null $created_by_user_id
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'provider_id',
    'name',
    'dosage',
    'frequency_label',
    'route',
    'instructions',
    'notes',
    'start_date',
    'end_date',
    'status',
    'created_by_user_id',
])]
class Medication extends Model
{
    /** @use HasFactory<MedicationFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_STOPPED = 'stopped';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_STOPPED,
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    protected static function booted(): void
    {
        static::creating(function (Medication $medication): void {
            if (empty($medication->uuid)) {
                $medication->uuid = (string) Str::uuid();
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
     * @return BelongsTo<Provider, $this>
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<MedicationSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(MedicationSchedule::class);
    }

    /**
     * @return HasMany<MedicationLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(MedicationLog::class);
    }

    /**
     * @return HasMany<MedicationReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(MedicationReminder::class);
    }
}
