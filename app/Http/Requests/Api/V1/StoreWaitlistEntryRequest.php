<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AppointmentWaitlistEntry;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWaitlistEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AppointmentWaitlistEntry::class) ?? false;
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
            'clinic_id' => [
                'required',
                'integer',
                Rule::exists('clinics', 'id')->where('tenant_id', $tenantId),
            ],
            'provider_id' => [
                'nullable',
                'integer',
                Rule::exists('providers', 'id')->where('tenant_id', $tenantId),
            ],
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where('tenant_id', $tenantId),
            ],
            'preferred_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
