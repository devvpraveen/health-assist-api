<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int $invoice_id
 * @property int $patient_id
 * @property int $amount_cents
 * @property string $currency
 * @property string $method
 * @property string $gateway
 * @property string|null $gateway_reference
 * @property string $status
 * @property string|null $idempotency_key
 * @property Carbon|null $paid_at
 * @property int|null $recorded_by_user_id
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'invoice_id',
    'patient_id',
    'amount_cents',
    'currency',
    'method',
    'gateway',
    'gateway_reference',
    'status',
    'idempotency_key',
    'paid_at',
    'recorded_by_user_id',
    'meta',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToTenant, HasFactory;

    public const METHOD_CASH = 'cash';

    public const METHOD_UPI = 'upi';

    public const METHOD_CARD = 'card';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_WALLET = 'wallet';

    public const METHOD_OTHER = 'other';

    public const METHODS = [
        self::METHOD_CASH,
        self::METHOD_UPI,
        self::METHOD_CARD,
        self::METHOD_BANK_TRANSFER,
        self::METHOD_WALLET,
        self::METHOD_OTHER,
    ];

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
        'gateway' => 'manual',
        'status' => self::STATUS_PENDING,
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
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
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
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

    /**
     * @return HasOne<Receipt, $this>
     */
    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
