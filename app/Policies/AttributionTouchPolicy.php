<?php

namespace App\Policies;

use App\Models\AttributionTouch;
use App\Models\User;

class AttributionTouchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('marketing.view')
            || $user->hasPermission('marketing.manage');
    }

    public function view(User $user, AttributionTouch $touch): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->hasPermission('marketing.view') || $user->hasPermission('marketing.manage'))) {
            return false;
        }

        return $touch->tenant_id === null || $user->tenant_id === $touch->tenant_id;
    }
}
