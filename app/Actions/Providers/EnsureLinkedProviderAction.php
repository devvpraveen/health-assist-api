<?php

namespace App\Actions\Providers;

use App\Actions\Organizations\EnsureTenantOrganizationAction;
use App\Models\Clinic;
use App\Models\Provider;
use App\Models\User;
use App\Support\TenantContext;

class EnsureLinkedProviderAction
{
    public function __construct(
        private EnsureTenantOrganizationAction $ensureTenantOrganizationAction,
    ) {}

    public function handle(User $user): Provider
    {
        $existing = Provider::query()
            ->withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->when(
                $user->tenant_id !== null,
                fn ($query) => $query->where('tenant_id', $user->tenant_id),
            )
            ->first();

        if ($existing) {
            return $existing;
        }

        $tenantId = $user->tenant_id ?? TenantContext::id();
        if ($tenantId === null) {
            throw new \RuntimeException('Cannot create provider without a tenant.');
        }

        $this->ensureTenantOrganizationAction->handle($user, $tenantId);

        $clinic = Clinic::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->firstOrFail();

        $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];
        $first = ($parts[0] ?? '') !== '' ? $parts[0] : 'Doctor';
        $last = ($parts[1] ?? '') !== '' ? $parts[1] : 'User';
        $display = trim((string) $user->name) !== '' ? trim((string) $user->name) : "Dr. {$first}";

        return Provider::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'clinic_id' => $clinic->id,
            'first_name' => $first,
            'last_name' => $last,
            'display_name' => $display,
            'type' => 'doctor',
            'verification_status' => 'pending',
            'is_public' => false,
            'status' => 'active',
        ]);
    }
}
