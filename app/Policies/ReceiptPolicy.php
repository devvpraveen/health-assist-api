<?php

namespace App\Policies;

use App\Models\Receipt;
use App\Models\User;

class ReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.payments.view');
    }

    public function view(User $user, Receipt $receipt): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $receipt->tenant_id
            && $user->hasPermission('billing.payments.view');
    }
}
