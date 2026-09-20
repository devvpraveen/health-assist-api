<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Refund;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Refund::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'payment_id' => [
                'required',
                'integer',
                Rule::exists('payments', 'id')->where('tenant_id', $tenantId),
            ],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'gateway_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
