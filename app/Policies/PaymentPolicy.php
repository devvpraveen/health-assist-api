<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.payments.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $payment->tenant_id
            && $user->hasPermission('billing.payments.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.payments.manage');
    }
}
