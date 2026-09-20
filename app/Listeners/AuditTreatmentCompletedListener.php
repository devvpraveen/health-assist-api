<?php

namespace App\Listeners;

use App\Events\TreatmentCompleted;
use App\Services\AuditLogger;

class AuditTreatmentCompletedListener
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(TreatmentCompleted $event): void
    {
        $this->auditLogger->log('clinical.treatment_completed', $event->treatmentPlan, [
            'treatment_plan_uuid' => $event->treatmentPlan->uuid,
            'patient_id' => $event->treatmentPlan->patient_id,
        ]);
    }
}
