<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $tenant_id
 * @property string $type
 * @property string $description
 * @property int $quantity
 * @property int $unit_price_cents
 * @property int $discount_cents
 * @property int|null $tax_rate_bps
 * @property int $tax_cents
 * @property int $line_total_cents
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int $sort_order
 */
#[Fillable([
    'invoice_id',
    'tenant_id',
    'type',
    'description',
    'quantity',
    'unit_price_cents',
    'discount_cents',
    'tax_rate_bps',
    'tax_cents',
    'line_total_cents',
    'reference_type',
    'reference_id',
    'sort_order',
])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use BelongsToTenant, HasFactory;

    public const TYPE_CONSULTATION = 'consultation';

    public const TYPE_ASSESSMENT = 'assessment';

    public const TYPE_TREATMENT = 'treatment';

    public const TYPE_PACKAGE = 'package';

    public const TYPE_SERVICE = 'service';

    public const TYPE_PRODUCT = 'product';

    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_CONSULTATION,
        self::TYPE_ASSESSMENT,
        self::TYPE_TREATMENT,
        self::TYPE_PACKAGE,
        self::TYPE_SERVICE,
        self::TYPE_PRODUCT,
        self::TYPE_OTHER,
    ];

    protected $attributes = [
        'quantity' => 1,
        'discount_cents' => 0,
        'tax_cents' => 0,
        'line_total_cents' => 0,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
            'discount_cents' => 'integer',
            'tax_rate_bps' => 'integer',
            'tax_cents' => 'integer',
            'line_total_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public static function computeLineTotals(int $quantity, int $unitPriceCents, int $discountCents, ?int $taxRateBps): array
    {
        $taxable = max(0, ($quantity * $unitPriceCents) - $discountCents);
        $taxCents = 0;

        if ($taxRateBps !== null && $taxRateBps > 0) {
            $taxCents = (int) round($taxable * $taxRateBps / 10000);
        }

        return [
            'tax_cents' => $taxCents,
            'line_total_cents' => $taxable + $taxCents,
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
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
