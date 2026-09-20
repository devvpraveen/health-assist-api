<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Invoice;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Invoice::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where('tenant_id', $tenantId),
            ],
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')->where('tenant_id', $tenantId),
            ],
            'currency' => ['sometimes', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
