<?php

namespace App\Policies;

use App\Models\PushDevice;
use App\Models\User;

class PushDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, PushDevice $device): bool
    {
        return $this->owns($user, $device);
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, PushDevice $device): bool
    {
        return $this->owns($user, $device);
    }

    public function delete(User $user, PushDevice $device): bool
    {
        return $this->owns($user, $device);
    }

    private function owns(User $user, PushDevice $device): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->id === $device->user_id
            && $user->tenant_id === $device->tenant_id;
    }
}
