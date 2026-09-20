<?php

namespace App\Policies;

use App\Models\Experiment;
use App\Models\User;

class ExperimentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('experiments.manage')
            || $user->hasPermission('marketing.view');
    }

    public function view(User $user, Experiment $experiment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->hasPermission('experiments.manage') || $user->hasPermission('marketing.view'))) {
            return false;
        }

        return $experiment->tenant_id === null || $user->tenant_id === $experiment->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('experiments.manage');
    }

    public function update(User $user, Experiment $experiment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('experiments.manage')) {
            return false;
        }

        return $experiment->tenant_id === null
            ? $user->isSuperAdmin()
            : $user->tenant_id === $experiment->tenant_id;
    }

    public function delete(User $user, Experiment $experiment): bool
    {
        return $this->update($user, $experiment);
    }

    public function expose(User $user, Experiment $experiment): bool
    {
        return $this->view($user, $experiment);
    }
}
