<?php

namespace App\Policies;

use App\Models\MarketingLead;
use App\Models\User;

class MarketingLeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->hasPermission('crm.manage')
            || $user->hasPermission('marketing.view');
    }

    public function view(User $user, MarketingLead $lead): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->hasPermission('crm.manage') || $user->hasPermission('marketing.view'))) {
            return false;
        }

        return $user->tenant_id === $lead->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasPermission('crm.manage');
    }

    public function update(User $user, MarketingLead $lead): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('crm.manage') && $user->tenant_id === $lead->tenant_id;
    }

    public function delete(User $user, MarketingLead $lead): bool
    {
        return $this->update($user, $lead);
    }
}
