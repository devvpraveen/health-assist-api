<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\User;

class PatientDocumentPolicy
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
            && $user->hasPermission('patients.documents.view');
    }

    public function view(User $user, PatientDocument $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $patient = $document->patient;
        if ($patient && $user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $document->tenant_id
            && $user->hasPermission('patients.documents.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('patients.documents.manage');
    }

    public function download(User $user, PatientDocument $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, PatientDocument $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $patient = $document->patient;
        if ($patient && $user->ownsPatient($patient)) {
            return true;
        }

        return $user->tenant_id === $document->tenant_id
            && $user->hasPermission('patients.documents.manage');
    }
}
