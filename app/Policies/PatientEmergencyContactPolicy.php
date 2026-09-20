<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientEmergencyContact;
use App\Models\User;

class PatientEmergencyContactPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        return $user->can('view', $patient);
    }

    public function view(User $user, PatientEmergencyContact $contact): bool
    {
        return $user->can('view', $contact->patient);
    }

    public function create(User $user, Patient $patient): bool
    {
        return $user->can('update', $patient);
    }

    public function update(User $user, PatientEmergencyContact $contact): bool
    {
        return $user->can('update', $contact->patient);
    }

    public function delete(User $user, PatientEmergencyContact $contact): bool
    {
        return $user->can('update', $contact->patient);
    }
}
