<?php

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Models\TenantBranding;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTenantAction
{
    /**
     * @param  array{name: string, slug?: string, status?: string, plan_code?: string|null}  $data
     */
    public function handle(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'status' => $data['status'] ?? 'active',
                'plan_code' => $data['plan_code'] ?? null,
            ]);

            TenantSetting::query()->create([
                'tenant_id' => $tenant->id,
                'settings' => [],
            ]);

            TenantBranding::query()->create([
                'tenant_id' => $tenant->id,
                'colors' => null,
            ]);

            return $tenant;
        });
    }
}
