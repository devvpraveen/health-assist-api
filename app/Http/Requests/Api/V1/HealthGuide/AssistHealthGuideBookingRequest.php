<?php

namespace App\Http\Requests\Api\V1\HealthGuide;

use Illuminate\Foundation\Http\FormRequest;

class AssistHealthGuideBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'clinic_id' => ['sometimes', 'nullable', 'integer', 'exists:clinics,id'],
            'patient_id' => ['sometimes', 'nullable', 'integer', 'exists:patients,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'service_id' => ['sometimes', 'nullable', 'integer', 'exists:services,id'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'confirm' => ['required', 'accepted'],
        ];
    }
}
