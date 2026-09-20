<?php

namespace App\Policies;

use App\Models\ClinicalSoapNote;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;

class ClinicalSoapNotePolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.notes.view');
    }

    public function view(User $user, ClinicalSoapNote $soapNote): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $soapNote->tenant_id
            && $user->hasPermission('clinical.notes.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.notes.manage');
    }

    public function update(User $user, ClinicalSoapNote $soapNote): bool
    {
        if ($soapNote->status === ClinicalDocumentWorkflow::STATUS_APPROVED
            || $soapNote->status === ClinicalDocumentWorkflow::STATUS_ARCHIVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $soapNote->tenant_id
            && $user->hasPermission('clinical.notes.manage');
    }

    public function delete(User $user, ClinicalSoapNote $soapNote): bool
    {
        if ($soapNote->status === ClinicalDocumentWorkflow::STATUS_APPROVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $soapNote->tenant_id
            && $user->hasPermission('clinical.notes.manage');
    }

    public function transition(User $user, ClinicalSoapNote $soapNote): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $soapNote->tenant_id
            && $user->hasPermission('clinical.notes.manage');
    }
}
