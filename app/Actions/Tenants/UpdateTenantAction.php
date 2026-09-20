<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;

class UpdateTenantAction
{
    /**
     * @param  array{name?: string, slug?: string, status?: string, plan_code?: string|null}  $data
     */
    public function handle(Tenant $tenant, array $data): Tenant
    {
        $tenant->fill($data);
        $tenant->save();

        return $tenant->refresh();
    }
}
