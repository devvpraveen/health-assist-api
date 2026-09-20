<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalProgressNote;
use App\Models\Patient;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalProgressNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [ClinicalProgressNote::class, $patient]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            'provider_id' => ['nullable', 'integer', Rule::exists('providers', 'id')->where('tenant_id', $tenantId)],
            'treatment_plan_id' => ['nullable', 'integer', Rule::exists('clinical_treatment_plans', 'id')->where('tenant_id', $tenantId)],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('tenant_id', $tenantId)],
            'noted_at' => ['required', 'date'],
            'note' => ['required', 'string'],
            'measurements' => ['nullable', 'array'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
