<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientHealthProfile;
use App\Models\User;

class PatientHealthProfilePolicy
{
    public function view(User $user, PatientHealthProfile $profile): bool
    {
        return $user->can('view', $profile->patient);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can('manageHealthProfile', $patient);
    }
}
