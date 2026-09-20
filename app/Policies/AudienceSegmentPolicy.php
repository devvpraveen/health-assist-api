<?php

namespace App\Policies;

use App\Models\AudienceSegment;
use App\Models\User;

class AudienceSegmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('marketing.view')
            || $user->hasPermission('marketing.manage');
    }

    public function view(User $user, AudienceSegment $segment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->hasPermission('marketing.view') || $user->hasPermission('marketing.manage'))) {
            return false;
        }

        return $user->tenant_id === $segment->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('marketing.manage');
    }

    public function update(User $user, AudienceSegment $segment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('marketing.manage') && $user->tenant_id === $segment->tenant_id;
    }

    public function delete(User $user, AudienceSegment $segment): bool
    {
        return $this->update($user, $segment);
    }
}
