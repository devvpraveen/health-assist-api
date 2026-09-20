<?php

namespace App\Policies;

use App\Models\ClinicalProgressNote;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;

class ClinicalProgressNotePolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.progress.view');
    }

    public function view(User $user, ClinicalProgressNote $note): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $note->tenant_id
            && $user->hasPermission('clinical.progress.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.progress.manage');
    }

    public function update(User $user, ClinicalProgressNote $note): bool
    {
        if ($note->status === ClinicalDocumentWorkflow::STATUS_APPROVED
            || $note->status === ClinicalDocumentWorkflow::STATUS_ARCHIVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $note->tenant_id
            && $user->hasPermission('clinical.progress.manage');
    }

    public function delete(User $user, ClinicalProgressNote $note): bool
    {
        if ($note->status === ClinicalDocumentWorkflow::STATUS_APPROVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $note->tenant_id
            && $user->hasPermission('clinical.progress.manage');
    }

    public function transition(User $user, ClinicalProgressNote $note): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $note->tenant_id
            && $user->hasPermission('clinical.progress.manage');
    }
}
