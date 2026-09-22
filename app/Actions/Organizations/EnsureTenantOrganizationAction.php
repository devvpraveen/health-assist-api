<?php

namespace App\Actions\Organizations;

use App\Models\Clinic;
use App\Models\Organization;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Str;

class EnsureTenantOrganizationAction
{
    /**
     * Ensure the tenant has an organization (and at least one clinic shell).
     */
    public function handle(?User $user = null, ?int $tenantId = null): Organization
    {
        $resolvedTenantId = $tenantId
            ?? $user?->tenant_id
            ?? TenantContext::id();

        if ($resolvedTenantId === null) {
            throw new \RuntimeException('Cannot ensure organization without a tenant.');
        }

        $organization = Organization::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $resolvedTenantId)
            ->orderBy('id')
            ->first();

        if ($organization === null) {
            $name = $user?->tenant?->name
                ?? ($user?->name ? trim($user->name).' Clinic' : 'Health Assist Clinic');

            $organization = Organization::query()->create([
                'tenant_id' => $resolvedTenantId,
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
                'status' => 'active',
                'description' => 'Care network profile for this clinic tenant.',
            ]);
        }

        $clinicExists = Clinic::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $resolvedTenantId)
            ->exists();

        if (! $clinicExists) {
            Clinic::query()->create([
                'tenant_id' => $resolvedTenantId,
                'organization_id' => $organization->id,
                'name' => $organization->name,
                'slug' => Str::slug($organization->name).'-'.Str::lower(Str::random(4)),
                'type' => 'clinic',
                'is_public' => false,
                'status' => 'active',
                'default_locale' => 'en',
            ]);
        }

        return $organization->fresh();
    }
}
