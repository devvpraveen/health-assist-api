<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Invoice;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return $this->user()?->can('update', $invoice) ?? false;
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
