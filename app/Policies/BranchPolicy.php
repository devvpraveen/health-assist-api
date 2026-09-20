<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('branches.view') || $user->isSuperAdmin();
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $branch->tenant_id
            && $user->hasPermission('branches.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('branches.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $branch->tenant_id
            && $user->hasPermission('branches.manage');
    }

    public function delete(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $branch->tenant_id
            && $user->hasPermission('branches.manage');
    }
}
