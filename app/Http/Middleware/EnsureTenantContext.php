<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * Resolve tenant context from the authenticated user.
     * Super admins may optionally switch context via X-Tenant-UUID / X-Tenant-Slug.
     * Regular users cannot forge tenant context from client headers.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $tenantId = $user?->tenant_id;

        if ($user?->isSuperAdmin()) {
            $override = $this->resolveSuperAdminTenantOverride($request);
            if ($override !== null) {
                $tenantId = $override;
            }
        }

        TenantContext::set($tenantId);

        return $next($request);
    }

    private function resolveSuperAdminTenantOverride(Request $request): ?int
    {
        $uuid = trim((string) $request->header('X-Tenant-UUID', ''));
        if ($uuid !== '') {
            $tenant = Tenant::query()->where('uuid', $uuid)->first();

            return $tenant?->id;
        }

        $slug = trim((string) $request->header('X-Tenant-Slug', ''));
        if ($slug !== '') {
            $tenant = Tenant::query()->where('slug', $slug)->first();

            return $tenant?->id;
        }

        return null;
    }
}
