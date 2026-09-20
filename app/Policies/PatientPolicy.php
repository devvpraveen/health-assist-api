<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('patients.view')
            || $user->hasLinkedPatient();
    }

    public function view(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('patients.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('patients.manage');
    }

    public function update(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('patients.manage');
    }

    public function delete(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('patients.manage');
    }

    public function viewHealthProfile(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }

    public function manageHealthProfile(User $user, Patient $patient): bool
    {
        return $this->update($user, $patient);
    }

    public function viewTimeline(User $user, Patient $patient): bool
    {
        return $this->view($user, $patient);
    }
}
