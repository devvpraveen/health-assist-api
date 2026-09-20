<?php

namespace App\Listeners;

use App\Events\DischargeCreated;
use App\Services\AuditLogger;

class AuditDischargeCreatedListener
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(DischargeCreated $event): void
    {
        $this->auditLogger->log('clinical.discharge_created_event', $event->dischargeSummary, [
            'discharge_uuid' => $event->dischargeSummary->uuid,
            'patient_id' => $event->dischargeSummary->patient_id,
            'status' => $event->dischargeSummary->status,
        ]);
    }
}
