<?php

namespace App\Policies;

use App\Models\EmailWorkflow;
use App\Models\User;

class EmailWorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('marketing.view')
            || $user->hasPermission('marketing.manage');
    }

    public function view(User $user, EmailWorkflow $workflow): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->hasPermission('marketing.view') || $user->hasPermission('marketing.manage'))) {
            return false;
        }

        return $workflow->tenant_id === null || $user->tenant_id === $workflow->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('marketing.manage');
    }

    public function update(User $user, EmailWorkflow $workflow): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('marketing.manage')) {
            return false;
        }

        return $workflow->tenant_id === null
            ? $user->isSuperAdmin()
            : $user->tenant_id === $workflow->tenant_id;
    }

    public function delete(User $user, EmailWorkflow $workflow): bool
    {
        return $this->update($user, $workflow);
    }

    public function subscribe(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('marketing.manage')
            || $user->tenant_id !== null;
    }
}
