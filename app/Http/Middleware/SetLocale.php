<?php

namespace App\Http\Middleware;

use App\Services\I18n\LanguageCatalog;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private LanguageCatalog $catalog) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if ($locale !== null) {
            $tenantId = TenantContext::id() ?? $request->user()?->tenant_id;
            $allowed = array_values(array_unique(array_merge(
                $this->catalog->enabledCodes('patient_app', $tenantId),
                $this->catalog->enabledCodes('clinic_app', $tenantId),
            )));

            if (in_array($locale, $allowed, true)) {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): ?string
    {
        $raw = $request->header('X-Locale');
        if (! is_string($raw) || $raw === '') {
            $raw = $request->header('Accept-Language');
        }

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $primary = trim(explode(',', $raw)[0]);
        $primary = trim(explode(';', $primary)[0]);

        if ($primary === '') {
            return null;
        }

        $normalized = strtolower(str_replace('_', '-', $primary));
        $base = explode('-', $normalized)[0];

        return $base !== '' ? $base : null;
    }
}
