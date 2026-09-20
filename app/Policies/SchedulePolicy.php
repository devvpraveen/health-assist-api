<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('schedules.view');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $schedule->tenant_id
            && $user->hasPermission('schedules.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('schedules.manage');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $schedule->tenant_id
            && $user->hasPermission('schedules.manage');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $schedule->tenant_id
            && $user->hasPermission('schedules.manage');
    }
}
