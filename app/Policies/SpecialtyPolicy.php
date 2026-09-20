<?php

namespace App\Policies;

use App\Models\Specialty;
use App\Models\User;

class SpecialtyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('specialties.view');
    }

    public function view(User $user, Specialty $specialty): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('specialties.view')) {
            return false;
        }

        return $specialty->tenant_id === null || $user->tenant_id === $specialty->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('specialties.manage');
    }

    public function update(User $user, Specialty $specialty): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($specialty->tenant_id === null) {
            return false;
        }

        return $user->tenant_id === $specialty->tenant_id
            && $user->hasPermission('specialties.manage');
    }

    public function delete(User $user, Specialty $specialty): bool
    {
        return $this->update($user, $specialty);
    }
}
