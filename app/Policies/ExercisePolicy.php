<?php

namespace App\Policies;

use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('clinical.exercises.view');
    }

    public function view(User $user, Exercise $exercise): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('clinical.exercises.view')) {
            return false;
        }

        return $exercise->tenant_id === null || $user->tenant_id === $exercise->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('clinical.exercises.manage');
    }

    public function update(User $user, Exercise $exercise): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($exercise->tenant_id === null) {
            return false;
        }

        return $user->tenant_id === $exercise->tenant_id
            && $user->hasPermission('clinical.exercises.manage');
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return $this->update($user, $exercise);
    }
}
