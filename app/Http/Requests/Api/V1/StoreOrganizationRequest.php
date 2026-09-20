<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();
        $tenantId = $user?->isSuperAdmin() && $this->filled('tenant_id')
            ? (int) $this->integer('tenant_id')
            : $user?->tenant_id;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('organizations', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
        ];

        if ($user?->isSuperAdmin()) {
            $rules['tenant_id'] = ['sometimes', 'integer', 'exists:tenants,id'];
        }

        return $rules;
    }
}
