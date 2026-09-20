<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ReceiptFactory;
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
 * @property string $number
 * @property int $amount_cents
 * @property string $currency
 * @property Carbon $issued_at
 */
#[Fillable([
    'uuid',
    'tenant_id',
    'payment_id',
    'invoice_id',
    'patient_id',
    'number',
    'amount_cents',
    'currency',
    'issued_at',
])]
class Receipt extends Model
{
    /** @use HasFactory<ReceiptFactory> */
    use BelongsToTenant, HasFactory;

    protected $attributes = [
        'currency' => 'INR',
    ];

    protected static function booted(): void
    {
        static::creating(function (Receipt $receipt): void {
            if (empty($receipt->uuid)) {
                $receipt->uuid = (string) Str::uuid();
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
            'issued_at' => 'datetime',
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
}
