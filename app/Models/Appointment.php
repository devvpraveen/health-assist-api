<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int $provider_id
 * @property int $clinic_id
 * @property int|null $branch_id
 * @property int|null $service_id
 * @property string $status
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property int $duration_minutes
 * @property string|null $reason
 * @property string|null $notes
 * @property int|null $booked_by_user_id
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $rescheduled_from_appointment_id
 * @property Carbon|null $checked_in_at
 * @property int|null $queue_number
 * @property Carbon|null $reminder_sent_at
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'provider_id',
    'clinic_id',
    'branch_id',
    'service_id',
    'status',
    'starts_at',
    'ends_at',
    'duration_minutes',
    'reason',
    'notes',
    'booked_by_user_id',
    'cancelled_at',
    'cancellation_reason',
    'rescheduled_from_appointment_id',
    'checked_in_at',
    'queue_number',
    'reminder_sent_at',
    'meta',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CHECKED_IN = 'checked_in';

    public const STATUS_IN_CONSULTATION = 'in_consultation';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_RESCHEDULED = 'rescheduled';

    public const STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_CONFIRMED,
        self::STATUS_CHECKED_IN,
        self::STATUS_IN_CONSULTATION,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
        self::STATUS_RESCHEDULED,
    ];

    /** @var list<string> */
    public const BUSY_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_CONFIRMED,
        self::STATUS_CHECKED_IN,
        self::STATUS_IN_CONSULTATION,
        self::STATUS_COMPLETED,
    ];

    /** @var list<string> */
    public const NON_BLOCKING_STATUSES = [
        self::STATUS_CANCELLED,
        self::STATUS_NO_SHOW,
        self::STATUS_RESCHEDULED,
    ];

    protected $attributes = [
        'status' => self::STATUS_CONFIRMED,
    ];

    protected static function booted(): void
    {
        static::creating(function (Appointment $appointment): void {
            if (empty($appointment->uuid)) {
                $appointment->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
            'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'queue_number' => 'integer',
            'reminder_sent_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeBusy(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::NON_BLOCKING_STATUSES);
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
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'rescheduled_from_appointment_id');
    }

    /**
     * @return HasMany<AppointmentReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class);
    }
}
