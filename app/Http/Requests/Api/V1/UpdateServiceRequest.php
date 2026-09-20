<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Service;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $this->user()?->can('update', $service) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        /** @var Service $service */
        $service = $this->route('service');

        return [
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('services', 'slug')
                    ->where('tenant_id', $tenantId)
                    ->where('clinic_id', $service->clinic_id)
                    ->ignore($service->id),
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
