<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tenants.view') || $user->isSuperAdmin();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->isSuperAdmin() || $user->hasPermission('tenants.view')) {
            return true;
        }

        return $user->tenant_id === $tenant->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('tenants.manage');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('tenants.manage');
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('tenants.manage');
    }
}
