<?php

namespace App\Actions\Billing;

use App\Models\BillingTaxRate;
use App\Services\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBillingTaxRateAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): BillingTaxRate
    {
        return DB::transaction(function () use ($data): BillingTaxRate {
            $taxRate = BillingTaxRate::query()->create([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => TenantContext::id(),
                'name' => $data['name'],
                'code' => $data['code'],
                'rate_bps' => $data['rate_bps'],
                'is_inclusive' => $data['is_inclusive'] ?? false,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->auditLogger->log('billing.tax_rate.created', $taxRate, [
                'tax_rate_uuid' => $taxRate->uuid,
                'code' => $taxRate->code,
            ]);

            return $taxRate;
        });
    }
}
