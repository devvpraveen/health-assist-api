<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalAssessment;
use App\Models\Patient;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [ClinicalAssessment::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'clinic_id' => ['nullable', 'integer', Rule::exists('clinics', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'template_key' => ['nullable', 'string', 'max:100'],
            'assessed_at' => ['required', 'date'],
            'chief_complaint' => ['nullable', 'string'],
            'findings' => ['nullable', 'array'],
            'summary' => ['nullable', 'string'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
