<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientPackage;
use App\Models\User;

class PatientPackagePolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('billing.packages.view');
    }

    public function view(User $user, PatientPackage $patientPackage): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patientPackage->tenant_id
            && $user->hasPermission('billing.packages.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('billing.packages.manage');
    }

    public function consume(User $user, PatientPackage $patientPackage): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patientPackage->tenant_id
            && $user->hasPermission('billing.packages.manage');
    }
}
