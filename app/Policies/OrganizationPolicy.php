<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('organizations.view') || $user->isSuperAdmin();
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $organization->tenant_id
            && $user->hasPermission('organizations.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('organizations.manage');
    }

    public function update(User $user, Organization $organization): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $organization->tenant_id
            && $user->hasPermission('organizations.manage');
    }

    public function delete(User $user, Organization $organization): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $organization->tenant_id
            && $user->hasPermission('organizations.manage');
    }
}
