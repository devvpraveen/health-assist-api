<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateInvoiceAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Invoice $invoice, array $data): Invoice
    {
        if (! $invoice->isEditable()) {
            throw ValidationException::withMessages([
                'invoice' => ['Only draft invoices can be updated.'],
            ]);
        }

        return DB::transaction(function () use ($invoice, $data): Invoice {
            $invoice->fill(collect($data)->only([
                'clinic_id',
                'appointment_id',
                'currency',
                'notes',
                'meta',
                'due_at',
            ])->all())->save();

            $this->auditLogger->log('billing.invoice.updated', $invoice, [
                'invoice_uuid' => $invoice->uuid,
            ]);

            return $invoice->fresh('items');
        });
    }
}
