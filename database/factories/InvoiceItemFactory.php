<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 1;
        $unitPrice = 10000;
        $discount = 0;
        $taxRateBps = 1800;
        $totals = InvoiceItem::computeLineTotals($quantity, $unitPrice, $discount, $taxRateBps);

        return [
            'invoice_id' => Invoice::factory(),
            'tenant_id' => 0,
            'type' => InvoiceItem::TYPE_CONSULTATION,
            'description' => 'Consultation',
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'discount_cents' => $discount,
            'tax_rate_bps' => $taxRateBps,
            'tax_cents' => $totals['tax_cents'],
            'line_total_cents' => $totals['line_total_cents'],
            'reference_type' => null,
            'reference_id' => null,
            'sort_order' => 0,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (InvoiceItem $item): void {
            if ((! $item->tenant_id || $item->tenant_id === 0) && $item->invoice_id) {
                $invoice = Invoice::query()->withoutGlobalScopes()->find($item->invoice_id);
                if ($invoice) {
                    $item->tenant_id = $invoice->tenant_id;
                }
            }
        });
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
        ]);
    }
}
