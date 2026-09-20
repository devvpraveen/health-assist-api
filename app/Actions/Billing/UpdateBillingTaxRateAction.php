<?php

namespace App\Actions\Billing;

use App\Models\BillingTaxRate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateBillingTaxRateAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BillingTaxRate $taxRate, array $data): BillingTaxRate
    {
        if ($taxRate->tenant_id === null) {
            throw ValidationException::withMessages([
                'tax_rate' => ['System tax rates cannot be updated via API.'],
            ]);
        }

        return DB::transaction(function () use ($taxRate, $data): BillingTaxRate {
            $taxRate->fill(collect($data)->only([
                'name',
                'code',
                'rate_bps',
                'is_inclusive',
                'is_active',
            ])->all())->save();

            $this->auditLogger->log('billing.tax_rate.updated', $taxRate, [
                'tax_rate_uuid' => $taxRate->uuid,
            ]);

            return $taxRate->fresh();
        });
    }
}
