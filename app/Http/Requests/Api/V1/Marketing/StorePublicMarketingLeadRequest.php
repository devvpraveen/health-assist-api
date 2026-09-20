<?php

namespace App\Http\Requests\Api\V1\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicMarketingLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:255'],
            'campaign' => ['nullable', 'string', 'max:255'],
            'provider_interest' => ['nullable', 'string', 'max:255'],
            'attribution' => ['nullable', 'array'],
            'website' => ['nullable', 'string', 'max:255'], // honeypot
        ];
    }
}
