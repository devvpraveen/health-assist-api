<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RescheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        return $this->user()?->can('reschedule', $appointment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
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
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
