<?php

namespace App\Policies;

use App\Models\SeoFaq;
use App\Models\User;

class SeoFaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('seo.view');
    }

    public function view(User $user, SeoFaq $faq): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('seo.view')) {
            return false;
        }

        if ($faq->tenant_id === null) {
            return true;
        }

        return $user->tenant_id === $faq->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('seo.manage');
    }

    public function update(User $user, SeoFaq $faq): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermission('seo.manage')) {
            return false;
        }

        if ($faq->tenant_id === null) {
            return $user->isSuperAdmin();
        }

        return $user->tenant_id === $faq->tenant_id;
    }

    public function delete(User $user, SeoFaq $faq): bool
    {
        return $this->update($user, $faq);
    }
}
