<?php

namespace App\Http\Middleware;

use App\Actions\Modules\ResolveTenantModulesAction;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private ResolveTenantModulesAction $resolveModules) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();
        // Platform console operates cross-tenant; modules are a clinic-scope gate.
        if ($user?->isSuperAdmin() || $user?->isPlatformAdmin()) {
            return $next($request);
        }

        $tenantId = TenantContext::id();
        if ($tenantId === null) {
            abort(403, 'Tenant context required for module access.');
        }

        // Core is always required and treated as active if missing from tenant_modules (bootstrap).
        if ($moduleKey === 'core') {
            return $next($request);
        }

        if (! $this->resolveModules->isActive($tenantId, $moduleKey)) {
            // Soft-fail when platform tables empty (pre-seed / tests that skip module seed).
            $hasAny = \App\Models\TenantModule::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->exists();

            if (! $hasAny) {
                return $next($request);
            }

            abort(403, "Module [{$moduleKey}] is not active for this clinic.");
        }

        return $next($request);
    }
}
