<?php

namespace App\Actions\Tenants;

use App\Actions\Modules\AssignPackageToTenantAction;
use App\Actions\Organizations\CreateOrganizationAction;
use App\Models\Organization;
use App\Models\Package;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvisionTenantOrganizationAction
{
    public function __construct(
        private CreateTenantAction $createTenant,
        private CreateOrganizationAction $createOrganization,
        private AssignPackageToTenantAction $assignPackage,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     slug?: string,
     *     status?: string,
     *     plan_code?: string|null,
     *     organization_name?: string|null,
     *     organization_slug?: string|null,
     *     package_key?: string|null
     * }  $data
     * @return array{tenant: Tenant, organization: Organization}
     */
    public function handle(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $tenant = $this->createTenant->handle([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'status' => $data['status'] ?? 'active',
                'plan_code' => $data['plan_code'] ?? ($data['package_key'] ?? 'starter'),
            ]);

            $previous = TenantContext::id();
            TenantContext::set($tenant->id);

            try {
                $organization = $this->createOrganization->handle([
                    'name' => $data['organization_name'] ?? $data['name'],
                    'slug' => $data['organization_slug'] ?? ($data['slug'] ?? Str::slug($data['name'])),
                    'status' => $data['status'] ?? 'active',
                    'tenant_id' => $tenant->id,
                ]);

                $packageKey = $data['package_key'] ?? 'starter';
                $package = Package::query()->where('key', $packageKey)->where('is_active', true)->first();
                if ($package !== null) {
                    $this->assignPackage->handle($tenant, $package, true);
                }
            } finally {
                TenantContext::set($previous);
            }

            return [
                'tenant' => $tenant->fresh(),
                'organization' => $organization->fresh(),
            ];
        });
    }
}
