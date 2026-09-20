<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where('tenant_id', $tenantId),
            ],
            'provider_id' => [
                'required',
                'integer',
                Rule::exists('providers', 'id')->where('tenant_id', $tenantId),
            ],
            'clinic_id' => [
                'nullable',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('tenant_id', $tenantId),
            ],
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('tenant_id', $tenantId),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'status' => ['sometimes', 'string', Rule::in([
                Appointment::STATUS_REQUESTED,
                Appointment::STATUS_CONFIRMED,
            ])],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
