<?php

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;

class ClinicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('clinics.view');
    }

    public function view(User $user, Clinic $clinic): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $clinic->tenant_id
            && $user->hasPermission('clinics.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('clinics.manage');
    }

    public function update(User $user, Clinic $clinic): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $clinic->tenant_id
            && $user->hasPermission('clinics.manage');
    }

    public function delete(User $user, Clinic $clinic): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $clinic->tenant_id
            && $user->hasPermission('clinics.manage');
    }

    public function manageSpecialties(User $user, Clinic $clinic): bool
    {
        return $this->update($user, $clinic);
    }
}
