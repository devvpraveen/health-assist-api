<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppAccount;

class WhatsAppAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('whatsapp.view');
    }

    public function view(User $user, WhatsAppAccount $account): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $account->tenant_id
            && $user->hasPermission('whatsapp.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('whatsapp.manage');
    }

    public function update(User $user, WhatsAppAccount $account): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $account->tenant_id
            && $user->hasPermission('whatsapp.manage');
    }

    public function delete(User $user, WhatsAppAccount $account): bool
    {
        return $this->update($user, $account);
    }

    public function manage(User $user, WhatsAppAccount $account): bool
    {
        return $this->update($user, $account);
    }
}
