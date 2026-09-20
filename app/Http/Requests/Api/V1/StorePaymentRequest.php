<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'invoice_id' => [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->where('tenant_id', $tenantId),
            ],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', Rule::in(Payment::METHODS)],
            'currency' => ['sometimes', 'string', 'size:3'],
            'gateway_reference' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
