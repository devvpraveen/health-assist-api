<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MedicationReminderFactory;
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
 * @property int|null $schedule_id
 * @property int $patient_id
 * @property string $channel
 * @property Carbon $scheduled_for
 * @property Carbon|null $sent_at
 * @property string $status
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'medication_id',
    'schedule_id',
    'patient_id',
    'channel',
    'scheduled_for',
    'sent_at',
    'status',
    'meta',
])]
class MedicationReminder extends Model
{
    /** @use HasFactory<MedicationReminderFactory> */
    use BelongsToTenant, HasFactory;

    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_DATABASE = 'database';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNEL_LOG = 'log';

    public const CHANNELS = [
        self::CHANNEL_MAIL,
        self::CHANNEL_DATABASE,
        self::CHANNEL_WHATSAPP,
        self::CHANNEL_LOG,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SENT,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    protected $attributes = [
        'channel' => self::CHANNEL_DATABASE,
        'status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (MedicationReminder $reminder): void {
            if (empty($reminder->uuid)) {
                $reminder->uuid = (string) Str::uuid();
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
            'sent_at' => 'datetime',
            'meta' => 'array',
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
     * @return BelongsTo<MedicationSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MedicationSchedule::class, 'schedule_id');
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
