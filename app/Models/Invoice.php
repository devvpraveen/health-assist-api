<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\InvoiceFactory;
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
 * @property int|null $clinic_id
 * @property int|null $appointment_id
 * @property int|null $issued_by_user_id
 * @property string|null $number
 * @property string $status
 * @property string $currency
 * @property int $subtotal_cents
 * @property int $discount_cents
 * @property int $tax_cents
 * @property int $total_cents
 * @property int $amount_paid_cents
 * @property int $amount_due_cents
 * @property Carbon|null $issued_at
 * @property Carbon|null $due_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $cancelled_at
 * @property string|null $notes
 * @property array<string, mixed>|null $meta
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'patient_id',
    'clinic_id',
    'appointment_id',
    'issued_by_user_id',
    'number',
    'status',
    'currency',
    'subtotal_cents',
    'discount_cents',
    'tax_cents',
    'total_cents',
    'amount_paid_cents',
    'amount_due_cents',
    'issued_at',
    'due_at',
    'paid_at',
    'cancelled_at',
    'notes',
    'meta',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToTenant, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ISSUED,
        self::STATUS_PARTIALLY_PAID,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
        self::STATUS_REFUNDED,
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
        'currency' => 'INR',
        'subtotal_cents' => 0,
        'discount_cents' => 0,
        'tax_cents' => 0,
        'total_cents' => 0,
        'amount_paid_cents' => 0,
        'amount_due_cents' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'discount_cents' => 'integer',
            'tax_cents' => 'integer',
            'total_cents' => 'integer',
            'amount_paid_cents' => 'integer',
            'amount_due_cents' => 'integer',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $subtotal = 0;
        $discount = 0;
        $tax = 0;

        foreach ($items as $item) {
            $subtotal += $item->unit_price_cents * $item->quantity;
            $discount += $item->discount_cents;
            $tax += $item->tax_cents;
        }

        $total = max(0, $subtotal - $discount + $tax);
        $amountDue = max(0, $total - $this->amount_paid_cents);

        $this->forceFill([
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'tax_cents' => $tax,
            'total_cents' => $total,
            'amount_due_cents' => $amountDue,
        ])->save();
    }

    /**
     * @return BelongsTo<Patient, $this>
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * @return BelongsTo<Clinic, $this>
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
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
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
