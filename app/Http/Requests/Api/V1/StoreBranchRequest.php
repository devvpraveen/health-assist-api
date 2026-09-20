<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Branch::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'suspended'])],
        ];
    }
}
