<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $invoice->tenant_id
            && $user->hasPermission('billing.invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('billing.invoices.manage');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if (! $invoice->isEditable()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $invoice->tenant_id
            && $user->hasPermission('billing.invoices.manage');
    }

    public function manageItems(User $user, Invoice $invoice): bool
    {
        return $this->update($user, $invoice);
    }

    public function issue(User $user, Invoice $invoice): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $invoice->tenant_id
            && $user->hasPermission('billing.invoices.manage');
    }

    public function cancel(User $user, Invoice $invoice): bool
    {
        return $this->issue($user, $invoice);
    }
}
