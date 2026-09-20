<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalTreatmentPlan;
use App\Models\Patient;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClinicalTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Patient $patient */
        $patient = $this->route('patient');

        return $this->user()?->can('create', [ClinicalTreatmentPlan::class, $patient]) ?? false;
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
            'title' => ['required', 'string', 'max:255'],
            'diagnosis_summary' => ['nullable', 'string'],
            'goals' => ['nullable', 'array'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'duration_weeks' => ['nullable', 'integer', 'min:1', 'max:520'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reassessment_date' => ['nullable', 'date'],
            'home_program_notes' => ['nullable', 'string'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
