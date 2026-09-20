<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalTreatmentSession;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalTreatmentSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalTreatmentSession $treatmentSession */
        $treatmentSession = $this->route('treatment_session');

        return $this->user()?->can('update', $treatmentSession) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'treatment_plan_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['sometimes', 'nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'provider_id' => ['sometimes', 'nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'session_at' => ['sometimes', 'date'],
            'modality' => ['sometimes', 'nullable', 'string', 'max:100'],
            'interventions' => ['sometimes', 'nullable', 'string'],
            'patient_response' => ['sometimes', 'nullable', 'string'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:480'],
            'status' => ['sometimes', 'string', Rule::in(ClinicalTreatmentSession::STATUSES)],
        ];
    }
}
