<?php

namespace App\Policies;

use App\Models\BillingPackage;
use App\Models\User;

class BillingPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.packages.view');
    }

    public function view(User $user, BillingPackage $package): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $package->tenant_id
            && $user->hasPermission('billing.packages.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.packages.manage');
    }

    public function update(User $user, BillingPackage $package): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $package->tenant_id
            && $user->hasPermission('billing.packages.manage');
    }

    public function delete(User $user, BillingPackage $package): bool
    {
        return $this->update($user, $package);
    }
}
