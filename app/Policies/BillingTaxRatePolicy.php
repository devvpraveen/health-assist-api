<?php

namespace App\Policies;

use App\Models\BillingTaxRate;
use App\Models\User;

class BillingTaxRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.invoices.view');
    }

    public function view(User $user, BillingTaxRate $taxRate): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('billing.invoices.view')) {
            return false;
        }

        return $taxRate->tenant_id === null || $user->tenant_id === $taxRate->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.invoices.manage');
    }

    public function update(User $user, BillingTaxRate $taxRate): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($taxRate->tenant_id === null) {
            return false;
        }

        return $user->tenant_id === $taxRate->tenant_id
            && $user->hasPermission('billing.invoices.manage');
    }

    public function delete(User $user, BillingTaxRate $taxRate): bool
    {
        return $this->update($user, $taxRate);
    }
}
