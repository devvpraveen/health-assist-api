<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve tenant for public progressive-journey routes from headers,
 * falling back to the configured demo tenant slug.
 */
class ResolvePublicTenant
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        $uuid = trim((string) $request->header('X-Tenant-UUID', ''));
        if ($uuid !== '') {
            $tenant = Tenant::query()->where('uuid', $uuid)->first();
        }

        if ($tenant === null) {
            $slug = trim((string) $request->header('X-Tenant-Slug', ''));
            if ($slug === '') {
                $slug = (string) config('health_guide.guest.default_tenant_slug', 'healthassist-demo');
            }
            $tenant = Tenant::query()->where('slug', $slug)->first();
        }

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant context is required.',
            ], 422);
        }

        TenantContext::set($tenant->id);
        $request->attributes->set('public_tenant', $tenant);

        return $next($request);
    }
}
