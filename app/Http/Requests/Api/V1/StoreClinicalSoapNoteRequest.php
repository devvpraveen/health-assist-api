<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalSoapNote;
use App\Models\Patient;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalSoapNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [ClinicalSoapNote::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['required', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'clinic_id' => ['nullable', 'integer', Rule::exists('clinics', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'assessment_id' => ['nullable', 'integer', Rule::exists('clinical_assessments', 'id')->where('tenant_id', $tenantId)],
            'subjective' => ['nullable', 'string'],
            'objective' => ['nullable', 'string'],
            'assessment' => ['nullable', 'string'],
            'plan' => ['nullable', 'string'],
            'session_date' => ['required', 'date'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
