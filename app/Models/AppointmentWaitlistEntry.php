<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\AppointmentWaitlistEntryFactory;
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
 * @property int $clinic_id
 * @property int|null $service_id
 * @property Carbon|null $preferred_date
 * @property string|null $notes
 * @property string $status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'provider_id',
    'clinic_id',
    'service_id',
    'preferred_date',
    'notes',
    'status',
])]
class AppointmentWaitlistEntry extends Model
{
    /** @use HasFactory<AppointmentWaitlistEntryFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_OFFERED = 'offered';

    public const STATUS_BOOKED = 'booked';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_OFFERED,
        self::STATUS_BOOKED,
        self::STATUS_CANCELLED,
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (AppointmentWaitlistEntry $entry): void {
            if (empty($entry->uuid)) {
                $entry->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
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
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
