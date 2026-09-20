<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalTreatmentSession;
use App\Models\Patient;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalTreatmentSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [ClinicalTreatmentSession::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'treatment_plan_id' => ['nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'provider_id' => ['nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'session_at' => ['required', 'date'],
            'modality' => ['nullable', 'string', 'max:100'],
            'interventions' => ['nullable', 'string'],
            'patient_response' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:480'],
            'status' => ['sometimes', 'string', Rule::in(ClinicalTreatmentSession::STATUSES)],
        ];
    }
}
