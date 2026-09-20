<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Specialty;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecialtyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Specialty $specialty */
        $specialty = $this->route('specialty');

        return $this->user()?->can('update', $specialty) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        $tenantKey = $tenantId === null ? 'system' : (string) $tenantId;
        /** @var Specialty $specialty */
        $specialty = $this->route('specialty');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('specialties', 'slug')
                    ->where('tenant_key', $tenantKey)
                    ->ignore($specialty->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Specialty::STATUSES)],
        ];
    }
}
