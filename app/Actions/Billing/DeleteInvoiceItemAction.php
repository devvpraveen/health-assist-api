<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteInvoiceItemAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(Invoice $invoice, InvoiceItem $item): void
    {
        if (! $invoice->isEditable()) {
            throw ValidationException::withMessages([
                'invoice' => ['Items can only be removed from draft invoices.'],
            ]);
        }

        if ($item->invoice_id !== $invoice->id) {
            throw ValidationException::withMessages([
                'item' => ['Invoice item does not belong to this invoice.'],
            ]);
        }

        DB::transaction(function () use ($invoice, $item): void {
            $this->auditLogger->log('billing.invoice_item.deleted', $item, [
                'invoice_uuid' => $invoice->uuid,
                'item_id' => $item->id,
            ]);

            $item->delete();
            $invoice->recalculateTotals();
        });
    }
}
