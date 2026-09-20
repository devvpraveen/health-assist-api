<?php

namespace App\Policies;

use App\Models\ClinicalAssessment;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;

class ClinicalAssessmentPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.assessments.view');
    }

    public function view(User $user, ClinicalAssessment $assessment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $assessment->tenant_id
            && $user->hasPermission('clinical.assessments.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.assessments.manage');
    }

    public function update(User $user, ClinicalAssessment $assessment): bool
    {
        if ($assessment->status === ClinicalDocumentWorkflow::STATUS_APPROVED
            || $assessment->status === ClinicalDocumentWorkflow::STATUS_ARCHIVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $assessment->tenant_id
            && $user->hasPermission('clinical.assessments.manage');
    }

    public function delete(User $user, ClinicalAssessment $assessment): bool
    {
        if ($assessment->status === ClinicalDocumentWorkflow::STATUS_APPROVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $assessment->tenant_id
            && $user->hasPermission('clinical.assessments.manage');
    }

    public function transition(User $user, ClinicalAssessment $assessment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $assessment->tenant_id
            && $user->hasPermission('clinical.assessments.manage');
    }
}
