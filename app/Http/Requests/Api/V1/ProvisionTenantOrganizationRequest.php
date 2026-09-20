<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProvisionTenantOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Tenant::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'unique:tenants,slug'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'plan_code' => ['nullable', 'string', 'max:100'],
            'organization_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'organization_slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'package_key' => ['sometimes', 'nullable', 'string', 'max:100', Rule::exists('packages', 'key')],
        ];
    }
}
