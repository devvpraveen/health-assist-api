<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalSoapNote;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalSoapNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalSoapNote $soapNote */
        $soapNote = $this->route('soap_note');

        return $this->user()?->can('update', $soapNote) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['sometimes', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'clinic_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinics', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['sometimes', 'nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'assessment_id' => ['sometimes', 'nullable', 'integer', Rule::exists('clinical_assessments', 'id')->where('tenant_id', $tenantId)],
            'subjective' => ['sometimes', 'nullable', 'string'],
            'objective' => ['sometimes', 'nullable', 'string'],
            'assessment' => ['sometimes', 'nullable', 'string'],
            'plan' => ['sometimes', 'nullable', 'string'],
            'session_date' => ['sometimes', 'date'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
