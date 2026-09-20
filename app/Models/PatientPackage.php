<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $patient_id
 * @property int $package_id
 * @property int|null $invoice_id
 * @property int|null $payment_id
 * @property int $sessions_total
 * @property int $sessions_used
 * @property int $sessions_remaining
 * @property Carbon $purchased_at
 * @property Carbon|null $expires_at
 * @property string $status
 * @property string $payment_status
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'package_id',
    'invoice_id',
    'payment_id',
    'sessions_total',
    'sessions_used',
    'sessions_remaining',
    'purchased_at',
    'expires_at',
    'status',
    'payment_status',
])]
class PatientPackage extends Model
{
    /** @use HasFactory<PatientPackageFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXHAUSTED = 'exhausted';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_EXHAUSTED,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELLED,
    ];

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_REFUNDED = 'refunded';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_PAID,
        self::PAYMENT_REFUNDED,
        self::PAYMENT_PARTIAL,
    ];

    protected $attributes = [
        'sessions_used' => 0,
        'status' => self::STATUS_ACTIVE,
        'payment_status' => self::PAYMENT_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (PatientPackage $package): void {
            if (empty($package->uuid)) {
                $package->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sessions_total' => 'integer',
            'sessions_used' => 'integer',
            'sessions_remaining' => 'integer',
            'purchased_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function consumeSession(int $count = 1): self
    {
        if ($count < 1) {
            throw ValidationException::withMessages([
                'sessions' => ['Session consume count must be at least 1.'],
            ]);
        }

        if ($this->status === self::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'package' => ['Cancelled packages cannot be consumed.'],
            ]);
        }

        if ($this->status === self::STATUS_EXPIRED
            || ($this->expires_at !== null && $this->expires_at->isPast())) {
            $this->forceFill(['status' => self::STATUS_EXPIRED])->save();

            throw ValidationException::withMessages([
                'package' => ['This package has expired.'],
            ]);
        }

        if ($this->status === self::STATUS_EXHAUSTED || $this->sessions_remaining < $count) {
            throw ValidationException::withMessages([
                'package' => ['No remaining sessions on this package.'],
            ]);
        }

        if ($this->payment_status !== self::PAYMENT_PAID) {
            throw ValidationException::withMessages([
                'package' => ['Only paid packages can be consumed.'],
            ]);
        }

        $this->sessions_used += $count;
        $this->sessions_remaining -= $count;

        if ($this->sessions_remaining === 0) {
            $this->status = self::STATUS_EXHAUSTED;
        }

        $this->save();

        return $this->fresh();
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<BillingPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(BillingPackage::class, 'package_id');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
