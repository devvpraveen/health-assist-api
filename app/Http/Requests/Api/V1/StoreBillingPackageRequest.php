<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BillingPackage;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBillingPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BillingPackage::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('billing_packages', 'slug')->where('tenant_id', $tenantId),
            ],
            'description' => ['nullable', 'string'],
            'session_count' => ['required', 'integer', 'min:1'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
