<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Services\AuditLogger;

class AuditPaymentReceivedListener
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(PaymentReceived $event): void
    {
        $this->auditLogger->log('billing.payment.received', $event->payment, [
            'payment_uuid' => $event->payment->uuid,
            'invoice_id' => $event->payment->invoice_id,
            'patient_id' => $event->payment->patient_id,
            'amount_cents' => $event->payment->amount_cents,
            'method' => $event->payment->method,
        ]);
    }
}
