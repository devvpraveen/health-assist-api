<?php

namespace App\Policies;

use App\Models\HealthGuideConversation;
use App\Models\User;

class HealthGuideConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HealthGuideConversation $conversation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $conversation->user_id === (int) $user->id) {
            return true;
        }

        return $user->tenant_id === $conversation->tenant_id
            && $user->hasPermission('health_guide.view');
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('health_guide.run')
            || $user->hasPermission('health_guide.view')
            || $user->tenant_id !== null;
    }

    public function run(User $user, HealthGuideConversation $conversation): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $conversation->user_id === (int) $user->id) {
            return true;
        }

        return $user->tenant_id === $conversation->tenant_id
            && $user->hasPermission('health_guide.run');
    }
}
