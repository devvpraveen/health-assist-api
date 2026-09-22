<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('appointments.view')
            || $user->hasLinkedPatient();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $appointment->loadMissing('patient');
        if ($appointment->patient && $user->ownsPatient($appointment->patient)) {
            return true;
        }

        if ($user->tenant_id !== $appointment->tenant_id) {
            return false;
        }

        if ($user->canViewClinicAppointmentBoard()) {
            return $user->hasPermission('appointments.view');
        }

        // Doctors: only appointments on their linked provider profile(s).
        return in_array((int) $appointment->provider_id, $user->linkedProviderIds(), true)
            && $user->hasPermission('appointments.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('appointments.manage')
            || $user->hasLinkedPatient();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id !== $appointment->tenant_id) {
            return false;
        }

        if ($user->canViewClinicAppointmentBoard()) {
            return $user->hasPermission('appointments.manage');
        }

        return in_array((int) $appointment->provider_id, $user->linkedProviderIds(), true)
            && $user->hasPermission('appointments.manage');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }

    public function reschedule(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }

    public function checkIn(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->tenant_id !== $appointment->tenant_id) {
            return false;
        }

        $canQueue = $user->hasPermission('appointments.queue.manage')
            || $user->hasPermission('appointments.manage');

        if (! $canQueue) {
            return false;
        }

        if ($user->canViewClinicAppointmentBoard()) {
            return true;
        }

        return in_array((int) $appointment->provider_id, $user->linkedProviderIds(), true);
    }

    public function updateStatus(User $user, Appointment $appointment): bool
    {
        return $this->checkIn($user, $appointment);
    }

    public function viewQueue(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('appointments.view')
            || $user->hasPermission('appointments.queue.manage');
    }

    public function viewAvailability(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('appointments.view')
            || $user->hasPermission('appointments.manage')
            || $user->hasPermission('schedules.view');
    }
}
