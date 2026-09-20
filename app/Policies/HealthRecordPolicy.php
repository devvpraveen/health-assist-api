<?php

namespace App\Policies;

use App\Models\HealthRecord;
use App\Models\Patient;
use App\Models\User;

class HealthRecordPolicy
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
            && $user->hasPermission('patients.records.view');
    }

    public function view(User $user, HealthRecord $record): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $patient = $record->patient;
        if ($patient && $user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $record->tenant_id
            && $user->hasPermission('patients.records.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('patients.records.manage');
    }

    public function update(User $user, HealthRecord $record): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $record->tenant_id
            && $user->hasPermission('patients.records.manage');
    }

    public function delete(User $user, HealthRecord $record): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $record->tenant_id
            && $user->hasPermission('patients.records.manage');
    }
}
