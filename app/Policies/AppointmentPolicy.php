<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $appointment->tenant_id
            && $user->hasPermission('appointments.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('appointments.manage');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $appointment->tenant_id
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

        return $user->tenant_id === $appointment->tenant_id
            && ($user->hasPermission('appointments.queue.manage')
                || $user->hasPermission('appointments.manage'));
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
