<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsAppConversation;

class WhatsAppConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('whatsapp.view');
    }

    public function view(User $user, WhatsAppConversation $conversation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $conversation->tenant_id
            && $user->hasPermission('whatsapp.view');
    }

    public function manage(User $user, WhatsAppConversation $conversation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->tenant_id === $conversation->tenant_id
            && $user->hasPermission('whatsapp.manage');
    }
}
