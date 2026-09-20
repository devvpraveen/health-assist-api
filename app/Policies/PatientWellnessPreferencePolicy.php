<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientWellnessPreference;
use App\Models\User;

class PatientWellnessPreferencePolicy
{
    public function view(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('wellness.view');
    }

    public function update(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('wellness.manage');
    }

    public function viewPreferences(User $user, PatientWellnessPreference $preference): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $preference->tenant_id
            && $user->hasPermission('wellness.view');
    }
}
