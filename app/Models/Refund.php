<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\RefundFactory;
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
 * @property int $payment_id
 * @property int $invoice_id
 * @property int $patient_id
 * @property int $amount_cents
 * @property string $currency
 * @property string|null $reason
 * @property string $status
 * @property string $gateway
 * @property string|null $gateway_reference
 * @property Carbon|null $processed_at
 * @property int|null $recorded_by_user_id
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'payment_id',
    'invoice_id',
    'patient_id',
    'amount_cents',
    'currency',
    'reason',
    'status',
    'gateway',
    'gateway_reference',
    'processed_at',
    'recorded_by_user_id',
])]
class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    protected $attributes = [
        'currency' => 'INR',
        'status' => self::STATUS_PENDING,
        'gateway' => 'manual',
    ];

    protected static function booted(): void
    {
        static::creating(function (Refund $refund): void {
            if (empty($refund->uuid)) {
                $refund->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
