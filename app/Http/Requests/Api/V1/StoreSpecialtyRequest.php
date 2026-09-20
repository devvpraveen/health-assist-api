<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Specialty;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecialtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Specialty::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        $tenantKey = $tenantId === null ? 'system' : (string) $tenantId;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('specialties', 'slug')->where('tenant_key', $tenantKey),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Specialty::STATUSES)],
        ];
    }
}
