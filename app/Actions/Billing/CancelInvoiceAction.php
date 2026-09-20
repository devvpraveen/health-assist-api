<?php

namespace App\Actions\Billing;

use App\Models\Invoice;
use App\Services\AuditLogger;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelInvoiceAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
    ) {}

    public function handle(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_PAID, Invoice::STATUS_REFUNDED], true)) {
                throw ValidationException::withMessages([
                    'invoice' => ['This invoice cannot be cancelled.'],
                ]);
            }

            if ($locked->amount_paid_cents > 0) {
                throw ValidationException::withMessages([
                    'invoice' => ['Invoices with payments must be refunded before cancel.'],
                ]);
            }

            $locked->forceFill([
                'status' => Invoice::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'amount_due_cents' => 0,
            ])->save();

            $fresh = $locked->fresh('items');

            $this->timelineRecorder->record(
                $fresh->patient,
                'billing.invoice.cancelled',
                'Invoice cancelled',
                subject: $fresh,
                meta: [
                    'invoice_uuid' => $fresh->uuid,
                    'invoice_number' => $fresh->number,
                ],
            );

            $this->auditLogger->log('billing.invoice.cancelled', $fresh, [
                'invoice_uuid' => $fresh->uuid,
            ]);

            return $fresh;
        });
    }
}
