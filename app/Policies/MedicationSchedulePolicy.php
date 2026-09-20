<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use App\Models\User;

class MedicationSchedulePolicy
{
    public function viewAny(User $user, Medication $medication): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $medication->tenant_id
            && $user->hasPermission('medications.view');
    }

    public function view(User $user, MedicationSchedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $schedule->tenant_id
            && $user->hasPermission('medications.view');
    }

    public function create(User $user, Medication $medication): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $medication->tenant_id
            && $user->hasPermission('medications.manage');
    }

    public function update(User $user, MedicationSchedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $schedule->tenant_id
            && $user->hasPermission('medications.manage');
    }

    public function delete(User $user, MedicationSchedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $schedule->tenant_id
            && $user->hasPermission('medications.manage');
    }
}
