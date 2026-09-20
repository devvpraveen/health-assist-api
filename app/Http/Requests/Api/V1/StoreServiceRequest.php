<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Clinic;
use App\Models\Service;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Service::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        /** @var Clinic|null $clinic */
        $clinic = $this->route('clinic');
        $clinicId = $clinic?->id ?? $this->input('clinic_id');

        return [
            'clinic_id' => [
                Rule::requiredIf($clinic === null),
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('services', 'slug')
                    ->where('tenant_id', $tenantId)
                    ->where('clinic_id', $clinicId),
            ],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'price_cents' => ['nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'is_public' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(Service::STATUSES)],
        ];
    }
}
