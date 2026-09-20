<?php

namespace App\Support;

use Illuminate\Support\Facades\Context;

class TenantContext
{
    public const CONTEXT_KEY = 'tenant_id';

    public static function set(?int $tenantId): void
    {
        if ($tenantId === null) {
            Context::forget(self::CONTEXT_KEY);

            return;
        }

        Context::addHidden(self::CONTEXT_KEY, $tenantId);
    }

    public static function id(): ?int
    {
        $value = Context::getHidden(self::CONTEXT_KEY);

        return $value === null ? null : (int) $value;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function clear(): void
    {
        Context::forget(self::CONTEXT_KEY);
    }
}
