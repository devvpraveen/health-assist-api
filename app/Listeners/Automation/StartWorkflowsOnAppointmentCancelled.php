<?php

namespace App\Listeners\Automation;

use App\Events\AppointmentCancelled;
use App\Services\Automation\WorkflowEngine;

class StartWorkflowsOnAppointmentCancelled
{
    public function __construct(private WorkflowEngine $engine) {}

    public function handle(AppointmentCancelled $event): void
    {
        $appointment = $event->appointment;
        $this->engine->dispatch('appointment.cancelled', (int) $appointment->tenant_id, [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'status' => $appointment->status,
            'appointment' => [
                'id' => $appointment->id,
                'status' => $appointment->status,
            ],
        ]);
    }
}
