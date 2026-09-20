<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    /**
     * @param  array{name: string, slug?: string, status?: string, tenant_id?: int}  $data
     */
    public function handle(array $data): Organization
    {
        $tenantId = TenantContext::id() ?? $data['tenant_id'] ?? null;

        return Organization::query()->create([
            'tenant_id' => $tenantId,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'status' => $data['status'] ?? 'active',
        ]);
    }
}
