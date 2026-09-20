<?php

namespace App\Listeners\Automation;

use App\Events\AppointmentCompleted;
use App\Services\Automation\WorkflowEngine;

class StartWorkflowsOnAppointmentCompleted
{
    public function __construct(private WorkflowEngine $engine) {}

    public function handle(AppointmentCompleted $event): void
    {
        $appointment = $event->appointment;
        $this->engine->dispatch('appointment.completed', (int) $appointment->tenant_id, [
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
