<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Provider;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Provider $provider */
        $provider = $this->route('provider');

        return $this->user()?->can('update', $provider) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'clinic_id' => [
                'sometimes',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('tenant_id', $tenantId),
            ],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
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
