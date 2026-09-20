<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Services\AuditLogger;

class AuditInvoiceIssuedListener
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(InvoiceIssued $event): void
    {
        $this->auditLogger->log('billing.invoice.issued', $event->invoice, [
            'invoice_uuid' => $event->invoice->uuid,
            'invoice_number' => $event->invoice->number,
            'patient_id' => $event->invoice->patient_id,
            'total_cents' => $event->invoice->total_cents,
        ]);
    }
}
