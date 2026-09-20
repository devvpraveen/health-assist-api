<?php

namespace App\Policies;

use App\Models\SeoEntity;
use App\Models\User;

class SeoEntityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('seo.view');
    }

    public function view(User $user, SeoEntity $entity): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('seo.view')) {
            return false;
        }

        if ($entity->tenant_id === null) {
            return true;
        }

        return $user->tenant_id === $entity->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('seo.manage');
    }

    public function update(User $user, SeoEntity $entity): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('seo.manage')) {
            return false;
        }

        if ($entity->tenant_id === null) {
            return $user->isSuperAdmin();
        }

        return $user->tenant_id === $entity->tenant_id;
    }

    public function delete(User $user, SeoEntity $entity): bool
    {
        return $this->update($user, $entity);
    }

    public function publish(User $user, SeoEntity $entity): bool
    {
        return $this->update($user, $entity);
    }
}
