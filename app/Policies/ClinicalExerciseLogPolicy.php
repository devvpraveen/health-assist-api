<?php

namespace App\Policies;

use App\Models\ClinicalExerciseLog;
use App\Models\ClinicalExercisePlan;
use App\Models\Patient;
use App\Models\User;

class ClinicalExerciseLogPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.exercises.view');
    }

    public function create(User $user, ClinicalExercisePlan $plan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $plan->tenant_id
            && $user->hasPermission('clinical.exercises.manage');
    }

    public function view(User $user, ClinicalExerciseLog $log): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $log->tenant_id
            && $user->hasPermission('clinical.exercises.view');
    }
}
