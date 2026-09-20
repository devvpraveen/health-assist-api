<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('branch')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}
