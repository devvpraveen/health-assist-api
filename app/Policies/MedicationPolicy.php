<?php

namespace App\Policies;

use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;

class MedicationPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('medications.view');
    }

    public function view(User $user, Medication $medication): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $patient = $medication->patient;
        if ($patient && $user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $medication->tenant_id
            && $user->hasPermission('medications.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('medications.manage');
    }

    public function update(User $user, Medication $medication): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $medication->tenant_id
            && $user->hasPermission('medications.manage');
    }

    public function delete(User $user, Medication $medication): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $medication->tenant_id
            && $user->hasPermission('medications.manage');
    }

    public function log(User $user, Medication $medication): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $patient = $medication->patient;
        if ($patient && $user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $medication->tenant_id
            && ($user->hasPermission('medications.manage') || $user->hasPermission('medications.view'));
    }
}
