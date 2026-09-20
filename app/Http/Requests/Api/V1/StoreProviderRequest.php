<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Provider;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Provider::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'clinic_id' => [
                'required',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', $tenantId),
            ],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(Provider::TYPES)],
            'bio' => ['nullable', 'string'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:100'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['string', 'max:20'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'verification_status' => ['sometimes', 'string', Rule::in(Provider::VERIFICATION_STATUSES)],
            'is_public' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(Provider::STATUSES)],
        ];
    }
}
