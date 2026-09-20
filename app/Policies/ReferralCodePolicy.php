<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\User;

class ReferralCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('marketing.view')
            || $user->hasPermission('marketing.manage');
    }

    public function view(User $user, ReferralCode $code): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->hasPermission('marketing.view') || $user->hasPermission('marketing.manage'))) {
            return false;
        }

        return $user->tenant_id === $code->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('marketing.manage');
    }

    public function convert(User $user, Referral $referral): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('marketing.manage') && $user->tenant_id === $referral->tenant_id;
    }
}
