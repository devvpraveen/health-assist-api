<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalAssessment;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalAssessment $assessment */
        $assessment = $this->route('assessment');

        return $this->user()?->can('update', $assessment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['sometimes', 'nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'clinic_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinics', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['sometimes', 'nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'template_key' => ['sometimes', 'nullable', 'string', 'max:100'],
            'assessed_at' => ['sometimes', 'date'],
            'chief_complaint' => ['sometimes', 'nullable', 'string'],
            'findings' => ['sometimes', 'nullable', 'array'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
