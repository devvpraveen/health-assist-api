<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ClinicalTreatmentPlan;
use App\Support\ClinicalDocumentWorkflow;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicalTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ClinicalTreatmentPlan $plan */
        $plan = $this->route('treatment_plan');

        return $this->user()?->can('update', $plan) ?? false;
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
            'title' => ['sometimes', 'string', 'max:255'],
            'diagnosis_summary' => ['sometimes', 'nullable', 'string'],
            'goals' => ['sometimes', 'nullable', 'array'],
            'frequency' => ['sometimes', 'nullable', 'string', 'max:100'],
            'duration_weeks' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:520'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
            'reassessment_date' => ['sometimes', 'nullable', 'date'],
            'home_program_notes' => ['sometimes', 'nullable', 'string'],
            'source' => ['sometimes', 'string', Rule::in(ClinicalDocumentWorkflow::SOURCES)],
        ];
    }
}
