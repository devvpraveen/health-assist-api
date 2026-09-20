<?php

namespace App\Actions\Roles;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;

class AssignRoleAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function handle(
        User $user,
        Role $role,
        int $tenantId,
        ?int $branchId = null,
        ?User $actor = null,
    ): User {
        $branchKey = $branchId === null ? 'none' : (string) $branchId;

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'tenant_id' => $tenantId,
                'branch_id' => $branchId,
                'branch_key' => $branchKey,
            ],
        ]);

        $this->auditLogger->log('roles.assign', $user, [
            'role_id' => $role->id,
            'role_slug' => $role->slug,
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
        ], $actor);

        return $user->refresh();
    }
}
