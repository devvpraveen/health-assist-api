<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('tenant')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
            'plan_code' => ['nullable', 'string', 'max:100'],
        ];
    }
}
