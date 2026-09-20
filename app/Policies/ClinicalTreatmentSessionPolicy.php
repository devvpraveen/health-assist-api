<?php

namespace App\Policies;

use App\Models\ClinicalTreatmentSession;
use App\Models\Patient;
use App\Models\User;

class ClinicalTreatmentSessionPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.treatment.view');
    }

    public function view(User $user, ClinicalTreatmentSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $session->tenant_id
            && $user->hasPermission('clinical.treatment.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.treatment.manage');
    }

    public function update(User $user, ClinicalTreatmentSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $session->tenant_id
            && $user->hasPermission('clinical.treatment.manage');
    }

    public function delete(User $user, ClinicalTreatmentSession $session): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $session->tenant_id
            && $user->hasPermission('clinical.treatment.manage');
    }
}
