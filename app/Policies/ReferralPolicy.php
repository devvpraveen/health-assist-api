<?php

namespace App\Policies;

use App\Models\Referral;
use App\Models\User;

class ReferralPolicy
{
    public function convert(User $user, Referral $referral): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('marketing.manage') && $user->tenant_id === $referral->tenant_id;
    }
}
