<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('providers.view');
    }

    public function view(User $user, Provider $provider): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $provider->tenant_id
            && $user->hasPermission('providers.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('providers.manage');
    }

    public function update(User $user, Provider $provider): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $provider->tenant_id
            && $user->hasPermission('providers.manage');
    }

    public function delete(User $user, Provider $provider): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $provider->tenant_id
            && $user->hasPermission('providers.manage');
    }

    public function syncRelations(User $user, Provider $provider): bool
    {
        return $this->update($user, $provider);
    }
}
