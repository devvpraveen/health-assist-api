<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BillingTaxRate;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BillingTaxRate $taxRate */
        $taxRate = $this->route('tax_rate');

        return $this->user()?->can('update', $taxRate) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var BillingTaxRate $taxRate */
        $taxRate = $this->route('tax_rate');
        $tenantId = TenantContext::id();

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('billing_tax_rates', 'code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($taxRate->id),
            ],
            'rate_bps' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'is_inclusive' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
