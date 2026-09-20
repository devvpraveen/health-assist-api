<?php

namespace App\Listeners\Marketing;

use App\Events\AppointmentBooked;
use App\Models\EmailSubscription;
use App\Models\EmailWorkflow;
use App\Services\Marketing\EmailWorkflowService;

/**
 * Stub listener: starts appointment_booked email workflows when a consented subscription exists.
 */
class StartAppointmentBookedEmailWorkflowListener
{
    public function __construct(private EmailWorkflowService $workflows) {}

    public function handle(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;
        $patient = $appointment->patient;

        if (! $patient?->email) {
            return;
        }

        $subscription = EmailSubscription::query()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $appointment->tenant_id)
            ->where('email', $patient->email)
            ->first();

        if (! $subscription?->isSubscribed()) {
            return;
        }

        $this->workflows->startForTrigger(EmailWorkflow::TRIGGER_APPOINTMENT_BOOKED, $subscription, [
            'appointment_id' => $appointment->id,
        ]);
    }
}
