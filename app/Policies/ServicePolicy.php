<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('services.view');
    }

    public function view(User $user, Service $service): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $service->tenant_id
            && $user->hasPermission('services.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('services.manage');
    }

    public function update(User $user, Service $service): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $service->tenant_id
            && $user->hasPermission('services.manage');
    }

    public function delete(User $user, Service $service): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $service->tenant_id
            && $user->hasPermission('services.manage');
    }
}
