<?php

namespace App\Actions\Billing;

use App\Models\BillingPackage;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddInvoiceItemAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Invoice $invoice, array $data): InvoiceItem
    {
        if (! $invoice->isEditable()) {
            throw ValidationException::withMessages([
                'invoice' => ['Items can only be added to draft invoices.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $data): InvoiceItem {
            $quantity = (int) ($data['quantity'] ?? 1);
            $unitPrice = (int) $data['unit_price_cents'];
            $discount = (int) ($data['discount_cents'] ?? 0);
            $taxRateBps = isset($data['tax_rate_bps']) ? (int) $data['tax_rate_bps'] : null;
            $totals = InvoiceItem::computeLineTotals($quantity, $unitPrice, $discount, $taxRateBps);

            $sortOrder = $data['sort_order']
                ?? ((int) $invoice->items()->max('sort_order') + 1);

            $referenceType = $data['reference_type'] ?? null;
            $referenceId = $data['reference_id'] ?? null;

            if (($data['type'] ?? null) === InvoiceItem::TYPE_PACKAGE && $referenceId && ! $referenceType) {
                $referenceType = BillingPackage::class;
            }

            $item = InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'tenant_id' => TenantContext::id() ?? $invoice->tenant_id,
                'type' => $data['type'],
                'description' => $data['description'],
                'quantity' => $quantity,
                'unit_price_cents' => $unitPrice,
                'discount_cents' => $discount,
                'tax_rate_bps' => $taxRateBps,
                'tax_cents' => $totals['tax_cents'],
                'line_total_cents' => $totals['line_total_cents'],
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'sort_order' => $sortOrder,
            ]);

            $invoice->recalculateTotals();

            $this->auditLogger->log('billing.invoice_item.added', $item, [
                'invoice_uuid' => $invoice->uuid,
                'item_id' => $item->id,
            ]);

            return $item;
        });
    }
}
