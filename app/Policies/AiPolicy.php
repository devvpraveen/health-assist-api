<?php

namespace App\Policies;

use App\Models\AiAuditLog;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use App\Models\AiUsageRecord;
use App\Models\User;

class AiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('ai.view');
    }

    public function viewUsage(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function viewAudit(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function viewModels(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function viewPrompts(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function manage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('ai.manage');
    }

    public function run(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('ai.run')
            || $user->hasPermission('ai.manage');
    }

    public function viewUsageRecord(User $user, AiUsageRecord $record): bool
    {
        return $this->viewUsage($user)
            && ($user->isSuperAdmin() || $user->tenant_id === $record->tenant_id);
    }

    public function viewAuditLog(User $user, AiAuditLog $log): bool
    {
        return $this->viewAudit($user)
            && ($user->isSuperAdmin() || $user->tenant_id === $log->tenant_id);
    }

    public function managePrompt(User $user, AiPrompt $prompt): bool
    {
        return $this->manage($user);
    }

    public function activateVersion(User $user, AiPromptVersion $version): bool
    {
        return $this->manage($user);
    }
}
