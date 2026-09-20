<?php

namespace App\Actions\Billing;

use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\Services\AuditLogger;
use App\Services\Billing\BillingNumberGenerator;
use App\Services\Patients\PatientTimelineRecorder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueInvoiceAction
{
    public function __construct(
        private AuditLogger $auditLogger,
        private PatientTimelineRecorder $timelineRecorder,
        private BillingNumberGenerator $numberGenerator,
    ) {}

    public function handle(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Invoice::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'invoice' => ['Only draft invoices can be issued.'],
                ]);
            }

            if ($locked->items()->count() === 0) {
                throw ValidationException::withMessages([
                    'invoice' => ['Cannot issue an invoice without line items.'],
                ]);
            }

            $locked->recalculateTotals();
            $locked->refresh();

            $locked->forceFill([
                'status' => Invoice::STATUS_ISSUED,
                'number' => $this->numberGenerator->nextInvoiceNumber($locked->tenant_id),
                'issued_at' => now(),
                'issued_by_user_id' => Auth::id() ?? $locked->issued_by_user_id,
                'amount_due_cents' => $locked->total_cents,
            ])->save();

            $fresh = $locked->fresh('items');

            $this->timelineRecorder->record(
                $fresh->patient,
                'billing.invoice.issued',
                'Invoice issued',
                subject: $fresh,
                meta: [
                    'invoice_uuid' => $fresh->uuid,
                    'invoice_number' => $fresh->number,
                    'total_cents' => $fresh->total_cents,
                ],
            );

            $this->auditLogger->log('billing.invoice.issued', $fresh, [
                'invoice_uuid' => $fresh->uuid,
                'invoice_number' => $fresh->number,
            ]);

            event(new InvoiceIssued($fresh));

            return $fresh;
        });
    }
}
