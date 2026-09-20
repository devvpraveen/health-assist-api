<?php

namespace App\Actions\Branches;

use App\Models\Branch;
use App\Models\Organization;
use App\Support\TenantContext;

class CreateBranchAction
{
    /**
     * @param  array{organization_id: int, name: string, code?: string|null, timezone?: string, status?: string}  $data
     */
    public function handle(array $data): Branch
    {
        $organization = Organization::query()->findOrFail($data['organization_id']);
        $tenantId = TenantContext::id() ?? $organization->tenant_id;

        return Branch::query()->create([
            'tenant_id' => $tenantId,
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'timezone' => $data['timezone'] ?? 'UTC',
            'status' => $data['status'] ?? 'active',
        ]);
    }
}
