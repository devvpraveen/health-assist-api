<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function view(User $user, Refund $refund): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $refund->tenant_id
            && (
                $user->hasPermission('billing.refunds.manage')
                || $user->hasPermission('billing.payments.view')
            );
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.refunds.manage');
    }
}
