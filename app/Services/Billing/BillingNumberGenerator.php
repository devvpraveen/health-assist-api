<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class BillingNumberGenerator
{
    public function nextInvoiceNumber(int $tenantId): string
    {
        return $this->nextNumber($tenantId, 'INV', Invoice::class, 'number');
    }

    public function nextReceiptNumber(int $tenantId): string
    {
        return $this->nextNumber($tenantId, 'RCP', Receipt::class, 'number');
    }

    /**
     * @param  class-string  $modelClass
     */
    protected function nextNumber(int $tenantId, string $prefix, string $modelClass, string $column): string
    {
        $date = now()->format('Ymd');
        $pattern = $prefix.'-'.$date.'-';

        return DB::transaction(function () use ($tenantId, $pattern, $modelClass, $column): string {
            $latest = $modelClass::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where($column, 'like', $pattern.'%')
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $sequence = 1;

            if (is_string($latest) && preg_match('/-(\d+)$/', $latest, $matches) === 1) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return $pattern.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }

    public function tenantIdOrFail(): int
    {
        $tenantId = TenantContext::id();

        if ($tenantId === null) {
            throw new \RuntimeException('Tenant context is required for billing number generation.');
        }

        return $tenantId;
    }
}
