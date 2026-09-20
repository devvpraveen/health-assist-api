<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\User;

class LanguagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('languages.view');
    }

    public function view(User $user, Language $language): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('languages.manage');
    }

    public function update(User $user, Language $language): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('languages.manage');
    }

    public function manageScopes(User $user, Language $language): bool
    {
        return $this->update($user, $language);
    }
}
