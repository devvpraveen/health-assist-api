<?php

namespace App\Policies;

use App\Models\ClinicalTreatmentPlan;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicalDocumentWorkflow;

class ClinicalTreatmentPlanPolicy
{
    public function viewAny(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.treatment.view');
    }

    public function view(User $user, ClinicalTreatmentPlan $plan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $plan->tenant_id
            && $user->hasPermission('clinical.treatment.view');
    }

    public function create(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('clinical.treatment.manage');
    }

    public function update(User $user, ClinicalTreatmentPlan $plan): bool
    {
        if ($plan->status === ClinicalDocumentWorkflow::STATUS_APPROVED
            || $plan->status === ClinicalDocumentWorkflow::STATUS_ARCHIVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $plan->tenant_id
            && $user->hasPermission('clinical.treatment.manage');
    }

    public function delete(User $user, ClinicalTreatmentPlan $plan): bool
    {
        if ($plan->status === ClinicalDocumentWorkflow::STATUS_APPROVED) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $plan->tenant_id
            && $user->hasPermission('clinical.treatment.manage');
    }

    public function transition(User $user, ClinicalTreatmentPlan $plan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $plan->tenant_id
            && $user->hasPermission('clinical.treatment.manage');
    }
}
