<?php

namespace App\Listeners;

use App\Events\RefundProcessed;
use App\Services\AuditLogger;

class AuditRefundProcessedListener
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(RefundProcessed $event): void
    {
        $this->auditLogger->log('billing.refund.processed', $event->refund, [
            'refund_uuid' => $event->refund->uuid,
            'payment_id' => $event->refund->payment_id,
            'invoice_id' => $event->refund->invoice_id,
            'amount_cents' => $event->refund->amount_cents,
        ]);
    }
}
