<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Patient;
use App\Models\PatientPackage;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [PatientPackage::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'package_id' => [
                'required',
                'integer',
                Rule::exists('billing_packages', 'id')->where('tenant_id', $tenantId),
            ],
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'create_invoice' => ['sometimes', 'boolean'],
            'tax_rate_bps' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
