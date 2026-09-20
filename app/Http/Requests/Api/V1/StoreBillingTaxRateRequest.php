<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BillingTaxRate;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillingTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BillingTaxRate::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('billing_tax_rates', 'code')->where('tenant_id', $tenantId),
            ],
            'rate_bps' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_inclusive' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
