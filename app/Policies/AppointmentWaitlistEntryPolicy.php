<?php

namespace App\Policies;

use App\Models\AppointmentWaitlistEntry;
use App\Models\User;

class AppointmentWaitlistEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('appointments.view');
    }

    public function view(User $user, AppointmentWaitlistEntry $entry): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $entry->tenant_id
            && $user->hasPermission('appointments.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('appointments.manage');
    }

    public function cancel(User $user, AppointmentWaitlistEntry $entry): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $entry->tenant_id
            && $user->hasPermission('appointments.manage');
    }
}
