<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppHandoff;

class WhatsAppHandoffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('whatsapp.view');
    }

    public function view(User $user, WhatsAppHandoff $handoff): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $handoff->tenant_id
            && $user->hasPermission('whatsapp.view');
    }

    public function manage(User $user, WhatsAppHandoff $handoff): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $handoff->tenant_id
            && $user->hasPermission('whatsapp.manage');
    }
}
