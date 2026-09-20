<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\ReportAnalysis;
use App\Models\User;

class ReportAnalysisPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('reports.view');
    }

    public function viewAnyForPatient(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('reports.view');
    }

    public function view(User $user, ReportAnalysis $analysis): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $analysis->tenant_id
            && $user->hasPermission('reports.view');
    }

    public function analyze(User $user, Patient $patient): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $patient->tenant_id
            && $user->hasPermission('reports.analyze');
    }

    public function review(User $user, ReportAnalysis $analysis): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $analysis->tenant_id
            && $user->hasPermission('reports.review');
    }
}
