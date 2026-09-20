<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BillingPackage;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BillingPackage $package */
        $package = $this->route('billing_package');

        return $this->user()?->can('update', $package) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var BillingPackage $package */
        $package = $this->route('billing_package');
        $tenantId = TenantContext::id();

        return [
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('billing_packages', 'slug')
                    ->where('tenant_id', $tenantId)
                    ->ignore($package->id),
            ],
            'description' => ['nullable', 'string'],
            'session_count' => ['sometimes', 'integer', 'min:1'],
            'validity_days' => ['nullable', 'integer', 'min:1'],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
