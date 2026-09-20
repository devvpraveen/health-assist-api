<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WellnessContent;

class WellnessContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('wellness.view');
    }

    public function view(User $user, WellnessContent $content): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('wellness.view')) {
            return false;
        }

        if ($content->tenant_id === null) {
            return true;
        }

        return $user->tenant_id === $content->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('wellness.manage');
    }

    public function update(User $user, WellnessContent $content): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('wellness.manage')) {
            return false;
        }

        if ($content->tenant_id === null) {
            return $user->isSuperAdmin();
        }

        return $user->tenant_id === $content->tenant_id;
    }

    public function delete(User $user, WellnessContent $content): bool
    {
        return $this->update($user, $content);
    }
}
