<?php

namespace App\Policies;

use App\Models\ClinicalDischargeSummary;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;

class ClinicalDischargeSummaryPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.discharge.view');
    }

    public function view(User $user, ClinicalDischargeSummary $summary): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $summary->tenant_id
            && $user->hasPermission('clinical.discharge.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.discharge.manage');
    }

    public function update(User $user, ClinicalDischargeSummary $summary): bool
    {
        if ($summary->status === ClinicalDocumentWorkflow::STATUS_APPROVED
            || $summary->status === ClinicalDocumentWorkflow::STATUS_ARCHIVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $summary->tenant_id
            && $user->hasPermission('clinical.discharge.manage');
    }

    public function delete(User $user, ClinicalDischargeSummary $summary): bool
    {
        if ($summary->status === ClinicalDocumentWorkflow::STATUS_APPROVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $summary->tenant_id
            && $user->hasPermission('clinical.discharge.manage');
    }

    public function transition(User $user, ClinicalDischargeSummary $summary): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $summary->tenant_id
            && $user->hasPermission('clinical.discharge.manage');
    }
}
